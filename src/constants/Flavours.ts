import { ReactComponent as AllianzLogo } from "../icons/Logo.svg"

export const DEFAULT: string = "DEFAULT"
export const ALLIANZ: string = "ALLIANZ"

export type Flavour = typeof DEFAULT | typeof ALLIANZ

export interface FlavourTheme {
  flavour: Flavour
  title: string
  logo: React.FunctionComponent<React.SVGProps<SVGSVGElement>>
}

const Default: FlavourTheme = {
  flavour: DEFAULT,
  title: "mindLAMP",
  logo: AllianzLogo,
}

const Allianz: FlavourTheme = {
  flavour: ALLIANZ,
  title: "Allianz",
  logo: AllianzLogo,
}

const Flavours: { [key: string]: FlavourTheme } = {
  default: Default,
  allianz: Allianz,
}

export default Flavours[process.env.REACT_APP_FLAVOUR || "Default"]
