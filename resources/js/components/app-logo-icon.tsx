import { cn } from "@/lib/utils"
import { useId, type SVGAttributes } from "react"

export default function AppLogoIcon({
	className,
	...props
}: SVGAttributes<SVGElement>) {
	// The cloud-note mark is a white-on-transparent PNG used as a mask, so it
	// takes on the current text colour like the wordmark next to it.
	const maskId = useId()

	return (
		<svg
			xmlns="http://www.w3.org/2000/svg"
			viewBox="0 0 400 120"
			className={cn("size-28", className)}
			{...props}>
			{/* Icon Start */}
			<mask
				id={maskId}
				maskUnits="userSpaceOnUse"
				x="0"
				y="0"
				width="120"
				height="120">
				<image
					href="/logo-mark.png"
					x="0"
					y="0"
					width="120"
					height="120"
				/>
			</mask>
			<rect
				x="0"
				y="0"
				width="120"
				height="120"
				fill="currentColor"
				mask={`url(#${maskId})`}
			/>
			{/* Icon End */}
			{/* Text Start */}
			<text
				x="140"
				y="80"
				fill="currentColor"
				fontSize="56"
				fontWeight="800"
				letterSpacing="-1"
				textLength="256"
				lengthAdjust="spacingAndGlyphs"
				style={{ fontFamily: "inherit" }}>
				Black Music
			</text>
			{/* Text End */}
		</svg>
	)
}
