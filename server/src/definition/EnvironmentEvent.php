<?php
require_once __DIR__ . '/../LAMP.php';
require_once __DIR__ . '/../driver/EnvironmentEventDriver.php';

/**
 * @OA\Schema(
 *   type="string",
 *   enum={"home", "school", "work", "hospital", "outside", "shopping", "transit"},
 *   description="The location-specific context for the environment."
 * )
 */
abstract class LocationContext {
    const Home = "home";
    const School = "school";
    const Work = "work";
    const Hospital = "hospital";
    const Outside = "outside";
    const Shopping = "shopping";
    const Transit = "transit";
}

/**
 * @OA\Schema(
 *   type="string",
 *   enum={"alone", "friends", "family", "peers", "crowd"},
 *   description="The social-specific context for the environment."
 * )
 */
abstract class SocialContext {
    const Alone = "alone";
    const Friends = "friends";
    const Family = "family";
    const Peers = "peers";
    const Crowd = "crowd";
}

/**
 * @OA\Schema(
 *   description="An event generated by the participant's location and if self-reported, the associated context."
 * )
 */
class EnvironmentEvent {
    use EnvironmentEventDriver;

    /**
     * @OA\Property(
     *   ref="#/components/schemas/Identifier",
     *   x={"type"="#/components/schemas/EnvironmentEvent"},
     *   description="The self-referencing identifier to this object."
     * )
     */
    public $id = null;

    /** 
     * @OA\Property(
     *   ref="#/components/schemas/Attachments",
     *   description="External or out-of-line objects attached to this object."
     * )
     */
    public $attachments = null;

    /** 
     * @OA\Property(
     *   ref="#/components/schemas/Timestamp",
     *   description="The date and time when this event was recorded."
     * )
     */
    public $timestamp = null;

    /** 
     * @OA\Property(
     *   type="array",
     *   @OA\Items(
     *     type="number",
     *     format="double"
     *   ),
     *   description="The GPS coordinates or approximate postal address recorded with the event."
     * )
     */
    public $coordinates = null;

    /** 
     * @OA\Property(
     *   type="number",
     *   format="double",
     *   description="The accuracy of provided GPS coordinates; if `null`, the coordinates represent an approximate postal address."
     * )
     */
    public $accuracy = null;

    /** 
     * @OA\Property(
     *   ref="#/components/schemas/LocationContext",
     *   description="The location context self-reported by the participant with the event."
     * )
     */
    public $location_context = null;

    /** 
     * @OA\Property(
     *   ref="#/components/schemas/SocialContext",
     *   description="The social context self-reported by the participant with the event."
     * )
     */
    public $social_context = null;

	/**
	 * @OA\Post(
	 *   path="/participant/{participant_id}/environment_event",
	 *   operationId="EnvironmentEvent::create",
	 *   tags={"EnvironmentEvent"},
	 *   x={"owner"={
	 *     "$ref"="#/components/schemas/EnvironmentEvent"}
	 *   },
	 *   summary="Get a single environment event, by identifier.",
	 *   description="Get a single environment event, by identifier.",
	 *   @OA\Parameter(
	 *     name="participant_id",
	 *     in="path",
	 *     required=true,
	 *     @OA\Schema(
	 *       ref="#/components/schemas/Identifier",
	 *       x={"type"={
	 *         "$ref"="#/components/schemas/Participant"}
	 *       },
	 *     )
	 *   ),
	 *   @OA\RequestBody(
	 *     required=true,
	 *     @OA\JsonContent(
	 *       ref="#/components/schemas/EnvironmentEvent"
	 *     ),
	 *   ),
	 *   @OA\Response(response=200, ref="#/components/responses/Success"),
	 *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
	 *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
	 *   @OA\Response(response=500, ref="#/components/responses/ServerFault"),
	 *   security={{"Authorization": {}}},
	 * )
	 */
	public static function create($participant_id, $environment_event) {
		$_id = (new TypeID($participant_id))->require([EnvironmentEvent::class]);
		self::authorize(function($type, $value) use($participant_id) {
			$_id1 = self::parent_of($participant_id, EnvironmentEvent::class,
				$type == AuthType::Researcher ? Researcher::class : Participant::class);
			return $value == ($type == AuthType::Researcher ? $_id1->part(1) : $_id1);
		});
		return self::_insert(null, null);
	}

	/**
	 * @OA\Put(
	 *   path="/environment_event/{environment_event_id}",
	 *   operationId="EnvironmentEvent::update",
	 *   tags={"EnvironmentEvent"},
	 *   x={"owner"={
	 *     "$ref"="#/components/schemas/EnvironmentEvent"}
	 *   },
	 *   summary="Get a single environment event, by identifier.",
	 *   description="Get a single environment event, by identifier.",
	 *   @OA\Parameter(
	 *     name="environment_event_id",
	 *     in="path",
	 *     required=true,
	 *     @OA\Schema(
	 *       ref="#/components/schemas/Identifier",
	 *       x={"type"={
	 *         "$ref"="#/components/schemas/EnvironmentEvent"}
	 *       },
	 *     )
	 *   ),
	 *   @OA\RequestBody(
	 *     required=true,
	 *     @OA\JsonContent(
	 *       ref="#/components/schemas/EnvironmentEvent"
	 *     ),
	 *   ),
	 *   @OA\Response(response=200, ref="#/components/responses/Success"),
	 *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
	 *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
	 *   @OA\Response(response=500, ref="#/components/responses/ServerFault"),
	 *   security={{"Authorization": {}}},
	 * )
	 */
	public static function update($environment_event_id, $environment_event) {
		$_id = (new TypeID($environment_event_id))->require([EnvironmentEvent::class]);
		self::authorize(function($type, $value) use($environment_event_id) {
			$_id1 = self::parent_of($environment_event_id, EnvironmentEvent::class,
				$type == AuthType::Researcher ? Researcher::class : Participant::class);
			return $value == ($type == AuthType::Researcher ? $_id1->part(1) : $_id1);
		});
		return self::_update(null, null);
	}

	/**
	 * @OA\Delete(
	 *   path="/environment_event/{environment_event_id}",
	 *   operationId="EnvironmentEvent::delete",
	 *   tags={"EnvironmentEvent"},
	 *   x={"owner"={
	 *     "$ref"="#/components/schemas/EnvironmentEvent"}
	 *   },
	 *   summary="Get a single environment event, by identifier.",
	 *   description="Get a single environment event, by identifier.",
	 *   @OA\Parameter(
	 *     name="environment_event_id",
	 *     in="path",
	 *     required=true,
	 *     @OA\Schema(
	 *       ref="#/components/schemas/Identifier",
	 *       x={"type"={
	 *         "$ref"="#/components/schemas/EnvironmentEvent"}
	 *       },
	 *     )
	 *   ),
	 *   @OA\Response(response=200, ref="#/components/responses/Success"),
	 *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
	 *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
	 *   @OA\Response(response=500, ref="#/components/responses/ServerFault"),
	 *   security={{"Authorization": {}}},
	 * )
	 */
	public static function delete($environment_event_id) {
		$_id = (new TypeID($environment_event_id))->require([EnvironmentEvent::class]);
		self::authorize(function($type, $value) use($environment_event_id) {
			$_id1 = self::parent_of($environment_event_id, EnvironmentEvent::class,
				$type == AuthType::Researcher ? Researcher::class : Participant::class);
			return $value == ($type == AuthType::Researcher ? $_id1->part(1) : $_id1);
		});
		return self::_delete(null);
	}

    /** 
     * @OA\Get(
     *   path="/environment_event/{environment_event_id}",
     *   operationId="EnvironmentEvent::view",
     *   tags={"EnvironmentEvent"},
     *   x={"owner"={
     *     "$ref"="#/components/schemas/EnvironmentEvent"}
     *   },
     *   summary="Get a single environment event, by identifier.",
     *   description="Get a single environment event, by identifier.",
     *   @OA\Parameter(
     *     name="environment_event_id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       ref="#/components/schemas/Identifier",
     *       x={"type"={
     *         "$ref"="#/components/schemas/EnvironmentEvent"}
     *       },
     *     )
     *   ),
     *   @OA\Response(response=200, ref="#/components/responses/Success"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/ServerFault"),
     *   security={{"Authorization": {}}},
     * )
     */
    public static function view($environment_event_id) {
        $_id = (new TypeID($environment_event_id))->require([EnvironmentEvent::class]);
        self::authorize(function($type, $value) use($environment_event_id) {
            $_id1 = self::parent_of($environment_event_id, EnvironmentEvent::class, 
                        $type == AuthType::Researcher ? Researcher::class : Participant::class);
            return $value == ($type == AuthType::Researcher ? $_id1->part(1) : $_id1);
        });
        return self::_select(null, null, $_id->part(1));
    }

    /** 
     * @OA\Get(
     *   path="/participant/{participant_id}/environment_event",
     *   operationId="EnvironmentEvent::all_by_participant",
     *   tags={"EnvironmentEvent"},
     *   x={"owner"={
     *     "$ref"="#/components/schemas/EnvironmentEvent"}
     *   },
     *   summary="Get the set of all environment events produced by a participant, by identifier.",
     *   description="Get the set of all environment events produced by a participant, by identifier.",
     *   @OA\Parameter(
     *     name="participant_id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       ref="#/components/schemas/Identifier",
     *       x={"type"={
     *         "$ref"="#/components/schemas/Participant"}
     *       },
     *     )
     *   ),
     *   @OA\Response(response=200, ref="#/components/responses/Success"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/ServerFault"),
     *   security={{"Authorization": {}}},
     * )
     */
    public static function all_by_participant($participant_id) {
        self::authorize(function($type, $value) use($participant_id) {
            if ($type == AuthType::Researcher) {
                $_id = self::parent_of($participant_id, Participant::class, Researcher::class);
                return $value == $_id->part(1);
            } else if ($type == AuthType::Participant) {
                return $value == $participant_id;
            } else return false;
        });
        return self::_select($participant_id);
    }

    /** 
     * @OA\Get(
     *   path="/study/{study_id}/environment_event",
     *   operationId="EnvironmentEvent::all_by_study",
     *   tags={"EnvironmentEvent"},
     *   x={"owner"={
     *     "$ref"="#/components/schemas/EnvironmentEvent"}
     *   },
     *   summary="Get the set of all environment events produced by participants enrolled in a study, by identifier.",
     *   description="Get the set of all environment events produced by participants enrolled in a study, by identifier.",
     *   @OA\Parameter(
     *     name="study_id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       ref="#/components/schemas/Identifier",
     *       x={"type"={
     *         "$ref"="#/components/schemas/Study"}
     *       },
     *     )
     *   ),
     *   @OA\Response(response=200, ref="#/components/responses/Success"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/ServerFault"),
     *   security={{"Authorization": {}}},
     * )
     */
    public static function all_by_study($study_id) {
        return EnvironmentEvent::all_by_researcher($study_id);
    }

    /** 
     * @OA\Get(
     *   path="/researcher/{researcher_id}/environment_event",
     *   operationId="EnvironmentEvent::all_by_researcher",
     *   tags={"EnvironmentEvent"},
     *   x={"owner"={
     *     "$ref"="#/components/schemas/EnvironmentEvent"}
     *   },
     *   summary="Get the set of all environment events produced by participants enrolled in any study conducted by a researcher, by identifier.",
     *   description="Get the set of all environment events produced by participants enrolled in any study conducted by a researcher, by identifier.",
     *   @OA\Parameter(
     *     name="researcher_id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       ref="#/components/schemas/Identifier",
     *       x={"type"={
     *         "$ref"="#/components/schemas/Researcher"}
     *       },
     *     )
     *   ),
     *   @OA\Response(response=200, ref="#/components/responses/Success"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/ServerFault"),
     *   security={{"Authorization": {}}},
     * )
     */
    public static function all_by_researcher($researcher_id) {
        $_id = (new TypeID($researcher_id))->require([Researcher::class, Study::class]);
        self::authorize(function($type, $value) use($_id) {
            return ($type == AuthType::Researcher && $value == $_id->part(1));
        });
        return self::_select(null, $_id->part(1));
    }

    /** 
     * @OA\Get(
     *   path="/environment_event",
     *   operationId="EnvironmentEvent::all",
     *   tags={"EnvironmentEvent"},
     *   x={"owner"={
     *     "$ref"="#/components/schemas/EnvironmentEvent"}
     *   },
     *   summary="Get the set of all environment events.",
     *   description="Get the set of all environment events.",
     *   @OA\Response(response=200, ref="#/components/responses/Success"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/ServerFault"),
     *   security={{"Authorization": {}}},
     * )
     */
    public static function all() {
        self::authorize(function($type, $value) {
            return false;
        });
        return self::_select();
    }
}
