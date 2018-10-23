<?php

/**
 * @OA\Info(
 *   title="LAMP Platform", 
 *   version="0.1",
 *   description="The LAMP Platform API.",
 *   termsOfService="http://psych.digital/lamp/terms/"
 * )
 */
class LAMP {
    use LAMPDriver;
    public static $api_index = null;

    /**
     * Bootstraps the entire server with all subclasses acting as API endpoints.
     */
    public static function start() {
        LAMP::$api_index = \OpenApi\scan(__DIR__);

        // Replace Flight's with our default return methods.
        Flight::map('json', function($data, $code = 200, $option = JSON_PRETTY_PRINT) {
            Flight::response()
                ->status($code)
                ->header('Content-Type', 'application/json; charset=utf8')
                ->write(json_encode($data, $option))
                ->send();
        });
        Flight::map('csv', function($data, $code = 200, $transpose = false) {
            $name = substr(strtr(strtok(Flight::request()->url, '?'), '/', '_'), 1);
            Flight::response()
                ->status($code)
                ->header('Content-Type', 'application/csv; charset=utf8')
                ->header('Content-Disposition', 'filename=' . $name . '.csv') // TODO: don't do this!
                ->write(Dynamics::csv_encode(Dynamics::multigroup($data), $transpose))
                ->send();
        });

        // Add CORS support.
        Flight::before('json', function() { 
            $h = getallheaders();
            $origin_header = isset($h['Origin']) ? $h['Origin'] : '*';
            header('Access-Control-Allow-Origin: '.$origin_header);
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET,PUT,POST,PATCH,DELETE,OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
        });
        Flight::route('OPTIONS *', function() {
            return Flight::json('');
        });

        // Using this is very convenient; we always receive an exception result.
        // If an LAMPException is thrown, we know to return that to the user normally.
        // Otherwise, encode the error in a log and notify the administrator about
        // the fatal error, and return the reference number to the user invoking it.
        Flight::map('error', function($e) {
            try {
                if ($e instanceof LAMPException)
                    return Flight::json(["error" => $e->getMessage()], $e->getCode());
                else return Flight::json(["error" => log::err($e)], 500);
            } catch(Throwable $t) {
                http_response_code(500);
                die("fatal exception: {$t->getMessage()}\n{$t->getTraceAsString()}");
            }
        });
        Flight::map('notFound', function() {
            throw new LAMPException("api endpoint does not exist", 404);
        });

        // Crawl all routes and set them up.
        $api = json_decode(json_encode(LAMP::$api_index), true);
        foreach ($api['paths'] as $path => $methods) {
            foreach ($methods as $method => $defn) {
                $route = strtoupper($method).' '.preg_replace('/{([\w_]+)}/', '@$1', $path);
                if (is_callable(explode('::', $defn['operationId'])))
                    LAMP::dynamic_route($route, $defn['operationId']);
            }
        }
        
        // Route the index or API explorer correctly.
        ini_set('short_open_tag', false);
        Flight::set('flight.views.path', realpath(__DIR__ . '/..') . '/templates');
    	Flight::route('GET /', function() {
            // TODO: Maybe use HTTP: X-Requested-With?
            if (Flight::request()->query->format === 'json') // TODO: YAML
                return Flight::json(LAMP::$api_index);
            return Flight::render('api_explorer.template.php', [
                'document_title' => 'LAMP API Explorer',
                'document_location' => ('http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/?format=json'),
            ]);
    	});

        // Start routing!
        Flight::start();
    }

    /** 
     * Dynamically routes an HTTP endpoint to a global or static function,
     * by using the Dynamics::invoke() method above and matching parameters.
     */
    private static function dynamic_route($route, $to) {
        Flight::route($route, function(...$args) use (&$to) {

            // Merge JSON/Body, URL Query, and URL Fragment parameters into one.
            $json_params = json_decode(Flight::request()->getBody(), true) ?: [];
            $url_params = array_pop($args)->params;
            $all_params = array_merge($json_params, $url_params);

            // Invoke the function matching all required parameters, bail otherwise.
            // Returning null means there was no data there (invalid URL?).
            $res = Dynamics::invoke($to, $all_params, function($name) {
                throw new LAMPException("missing paramter $name", 400);
            });

            // TODO: Make sure `null` is different than `[]`.
            // If nothing is there, return `[]`, but if the object is invalid, return `null`.
            if ($res === null) 
                $res = [];
                //throw new LAMPException("no such objects", 404);

            // Filter the result using an optional JSON XPath (jmespath.org).
            if (Flight::request()->query->xpath != null) {
                $def = new JmesPath\FnDispatcher();
                $xp = new JmesPath\AstRuntime(null, function ($fn, array $args) use ($def) {
                    if (isset(self::xpath_builtins()[$fn]))
                        return self::xpath_builtins()[$fn]($args);
                    return $def($fn, $args);
                });

                $res = json_decode(json_encode($res)); // needed because LAMPID/JsonSerializable
                try {
                    $res = $xp(Flight::request()->query->xpath, $res);
                } catch (Exception $e) {
                    throw new LAMPException("invalid xpath query: '{$e->getMessage()}'", 500);
                }
            }

            // Return a "result" JSON string from the result value of the call.
            // If export options (CSV, XML) are provided, export in those formats.
            $res = (is_array($res) ? $res : [$res]);
            if (substr(Flight::request()->query->export ?: '', 0, 3) === 'csv') {
                $trs = substr(Flight::request()->query->export, 4, 9) === 'transpose';
                $res = array_map(function($x) { return Dynamics::flatten($x, true); }, $res);
                return Flight::csv($res, 200, $trs);
            } else return Flight::json(["count" => count($res), "result" => $res]);
        }, true);
    }

    /**
     * TODO: XPath Builtins
     */
    private static function xpath_builtins() {
        static $builtins = null;
        if ($builtins === null) $builtins = [
            'date' => function(array $args) { 
                $format = isset($args[1]) && !empty($args[1]) ? $args[1] : 'l\, F jS\, Y h:i:s A';
                return date($format, $args[0]); 
            },
            'split' => function(array $args) { return explode($args[0], $args[1]); },
            'add' => function(array $args) { return $args[0] + $args[1]; },
            'sub' => function(array $args) { return $args[0] - $args[1]; },
            'mul' => function(array $args) { return $args[0] * $args[1]; },
            'div' => function(array $args) { return $args[0] / $args[1]; },
            'mod' => function(array $args) { return $args[0] % $args[1]; },
        ];
        return $builtins;
    }

    /**
     * @OA\SecurityScheme(
     *   securityScheme="Authorization",
     *   name="Authorization",
     *   type="apiKey",
     *   in="header",
     * )
     * 
     * @OA\SecurityScheme(
     *   securityScheme="AuthorizationLegacy",
     *   name="auth",
     *   type="apiKey",
     *   in="query",
     * )
     */
    public static function auth_header() {
        $header = ' : ';
        if (isset(getallheaders()['Authorization']))
            $header = str_replace('Basic ', '', getallheaders()['Authorization']);
        else $header = Flight::request()->query['auth'];
        return explode(':', $header, 2);
    }

    // AES265 encryption using the DB_CRYPT_HIPAA constant, as used by the LAMP DB currently.
    // If the data could not be encrypted or is invalid, returns `null`.
    // If default is NOT false, the input data is returned instead of null.
    // Mode may be 'hipaa' or 'oauth' depending on usage. See docs.
    // AES256 encryption using DB_CRYPT_OAUTH for passwords; uses CBC+IV instead of ECB.
    public static function encrypt($data, $default = false, $mode = 'hipaa') {
        if ($mode === 'hipaa') {
            if ($data === null) return null;
            $x = openssl_encrypt($data, 'aes-256-ecb', DB_CRYPT_HIPAA);
            return $x === false ? ($default === false ? null : $data) : $x; 
        } else if ($mode === 'oauth') {
            return null; // TODO
        } else return null;
    }

    // AES265 decryption using the DB_CRYPT_HIPAA constant, as used by the LAMP DB currently.
    // If the data could not be decrypted or is invalid, returns `null`.
    // If default is NOT false, the input data is returned instead of null.
    // Mode may be 'hipaa' or 'oauth' depending on usage. See docs.
    // AES256 decryption using DB_CRYPT_OAUTH for passwords; uses CBC+IV instead of ECB.
    // Note: seems to cause every other character to be \0 unless removed. PKCS-7?
    public static function decrypt($data, $default = false, $mode = 'hipaa') {
        if ($mode === 'hipaa') {
            if ($data === null) return null;
            $x = openssl_decrypt($data, 'aes-256-ecb', DB_CRYPT_HIPAA);
            return $x === false ? ($default === false ? null : $data) : $x; 
        } else if ($mode === 'oauth') {
            $data = base64_decode(str_replace(' ', '+', $data));
            $ivl = openssl_cipher_iv_length('aes-256-cbc');
            if (strlen($data) <= $ivl) return null;
            $x = openssl_decrypt(substr($data, $ivl), 'aes-256-cbc', hex2bin(DB_CRYPT_OAUTH), 
                  OPENSSL_RAW_DATA, substr($data, 0, $ivl));
            return $x === false ? null : preg_replace('/\\0/', '', $x); 
        } else return null;
    }
}

/**
 * @OA\Schema(
 *   type="string",
 *   enum={"root", "researcher", "participant"}
 * )
 */
abstract class AuthType extends LAMP {
    const Root = 'root';
    const Researcher = 'researcher';
    const Participant = 'participant';
}

/**
 * @OA\Schema(
 *   schema="Identifier",
 *   type="string",
 *   description="A globally unique reference for objects within the LAMP platform.",
 * )
 * 
 * Use `require()` to restrict the ID to certain prefix(s) and `part()` to 
 * access ID components safely. Note: DO NOT use the reserved character ':' in any 
 * component strings.
 */
class LAMPID implements JsonSerializable {
    private $components = [];
    public function __construct($value) {
        if (is_string($value)) {
            $this->components = explode(":", base64_decode(strtr($value, '_-~', '+/=')));
        } else if (is_array($value)) 
            $this->components = $value;
        else throw new Exception('invalid LAMP ID value');
    }
    public function jsonSerialize() {
        return strtr(base64_encode(implode(':', $this->components)), '+/=', '_-~');
    }
    public function require($match_prefix) {
        if (!in_array($this->components[0], $match_prefix))
            throw new LAMPException("invalid identifier", 403); 
        return $this;
    }
    public function part($idx) {
        return isset($this->components[$idx]) ? $this->components[$idx] : null;
    }
}

/**
 * @OA\Schema()
 */
class CalendarComponents extends LAMP {

    /** 
     * @OA\Property(
     *   type="integer", 
     *   format="int64"
     * )
     */
    public $year = null;

    /** 
     * @OA\Property(
     *   type="integer", 
     *   format="int64"
     * )
     */
    public $month = null;

    /** 
     * @OA\Property(
     *   type="integer", 
     *   format="int64"
     * )
     */
    public $day = null;

    /** 
     * @OA\Property(
     *   type="integer", 
     *   format="int64"
     * )
     */
    public $hour = null;

    /** 
     * @OA\Property(
     *   type="integer", 
     *   format="int64"
     * )
     */
    public $minute = null;

    /** 
     * @OA\Property(
     *   type="integer", 
     *   format="int64"
     * )
     */
    public $second = null;

    /** 
     * @OA\Property(
     *   type="integer", 
     *   format="int64"
     * )
     */
    public $millisecond = null;

    /** 
     * @OA\Property(
     *   type="integer", 
     *   format="int64"
     * )
     */
    public $weekday = null;

    /** 
     * @OA\Property(
     *   type="integer", 
     *   format="int64"
     * )
     */
    public $ordinal = null;

    /** 
     * @OA\Property(
     *   type="integer", 
     *   format="int64"
     * )
     */
    public $week_of_month = null;

    /** 
     * @OA\Property(
     *   type="integer", 
     *   format="int64"
     * )
     */
    public $week_of_year = null;

    public function __construct($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $millisecond = null, $weekday = null, $ordinal = null, $week_of_month = null, $week_of_year = null) {
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
        $this->hour = $hour;
        $this->minute = $minute;
        $this->second = $second;
        $this->millisecond = $millisecond;
        $this->weekday = $weekday;
        $this->ordinal = $ordinal;
        $this->week_of_month = $week_of_month;
        $this->week_of_year = $week_of_year;
    }
}

/**
 * @OA\Schema()
 */
class DurationInterval extends LAMP {

    /** 
     * @OA\Property(
     *   ref="#/components/schemas/Timestamp"
     * )
     */
    public $start = null;

    /** 
     * @OA\Property(
     *   type="array", 
     *   @OA\Items(ref="#/components/schemas/CalendarComponents")
     * )
     */
    public $interval = null;

    /** 
     * @OA\Property(
     *   type="integer", 
     *   format="int64"
     * )
     */
    public $repeat_count = null; 

    /** 
     * @OA\Property(
     *   ref="#/components/schemas/Timestamp"
     * )
     */
    public $end = null; 

    public function __construct($start = null, $interval = null, $repeat_count = null, $end = null) {
        $this->start = $start;
        $this->interval = $interval;
        $this->repeat_count = $repeat_count;
        $this->end = $end;
    }
}

/**
 * @OA\Schema(
 *   schema="Error",
 *   @OA\Property(
 *     property="message",
 *     type="string"
 *   ),
 *   @OA\Property(
 *     property="code",
 *     type="integer",
 *     format="int32"
 *   ),
 * )
 * 
 * API should throw this to cleanly transfer any HTTP errors into clients.
 */
class LAMPException extends Exception {}

/**
 * @OA\Schema(
 *   schema="Timestamp",
 *   type="integer",
 *   format="int64"
 * )
 */

/**
 * @OA\Schema(
 *   schema="Attachments",
 *   @OA\AdditionalProperties()
 * )
 */

/**
 * @OA\Schema(
 *   schema="Response",
 *   @OA\Property(
 *     property="result",
 *     type="array",
 *     @OA\Items(),
 *   ),
 *   @OA\Property(
 *     property="count",
 *     type="integer",
 *     format="int32",
 *   ),
 * )
 */

/**
 * @OA\Parameter(
 *   parameter="XPath",
 *   description="See the JMESPath specification for details; prefer applying modifications on the client-side instead of using this query parameter.",
 *   name="xpath",
 *   in="query",
 *   required=false,
 *   @OA\Schema(
 *     type="string",
 *   ),
 * )
 * 
 * @OA\Parameter(
 *   parameter="Export",
 *   description="Switch from raw JSON output to a 2D CSV dataframe, optionally column-transposed.",
 *   name="export",
 *   in="query",
 *   required=false,
 *   @OA\Schema(
 *     type="string",
 *     enum={"csv", "csv-transpose"}
 *   ),
 * )
 */

/**
 * @OA\Response(
 *   response="Success", 
 *   description="Success", 
 *   @OA\JsonContent(ref="#/components/schemas/Response")
 * )
 * 
 * @OA\Response(
 *   response="Forbidden", 
 *   description="Forbidden", 
 *   @OA\JsonContent(ref="#/components/schemas/Error")
 * )
 * 
 * @OA\Response(
 *   response="NotFound", 
 *   description="NotFound", 
 *   @OA\JsonContent(ref="#/components/schemas/Error")
 * )
 * 
 * @OA\Response(
 *   response="ServerFault", 
 *   description="ServerFault", 
 *   @OA\JsonContent(ref="#/components/schemas/Error")
 * )
 */

/**
 * All LAMP API actions are designated from their class definitions to specific
 * drivers implemented as PHP Traits. If the implementation detail underlying the
 * API changes, add a new `LAMPDriver` and/or extend it for new functionality.
 */
trait LAMPDriver {}

/*
-- Utility function that removes keys from FOR JSON output.
-- i.e. UNWRAP_JSON([{'val':1,{'val':2},{'val':'cell'}], 'val') => [1,2,'cell']
CREATE OR ALTER FUNCTION FUNCTION
    dbo.UNWRAP_JSON(@json nvarchar(max), @key nvarchar(400)) RETURNS nvarchar(max)
AS BEGIN
    RETURN REPLACE(REPLACE(@json, FORMATMESSAGE('{"%s":', @key),''), '}','')
END;
*/