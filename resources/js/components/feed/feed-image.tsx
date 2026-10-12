import { useState, type ImgHTMLAttributes } from "react"

/**
 * An <img> that disappears if its file fails to load, so a missing upload
 * leaves the muted placeholder behind it instead of a broken-image icon.
 */
export function FeedImage({
	alt = "",
	...props
}: ImgHTMLAttributes<HTMLImageElement>) {
	const [failed, setFailed] = useState(false)

	if (failed || !props.src) {
		return null
	}

	return (
		<img
			alt={alt}
			loading="lazy"
			onError={() => setFailed(true)}
			{...props}
		/>
	)
}
