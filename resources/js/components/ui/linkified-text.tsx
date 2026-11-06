import { Fragment } from "react"
import { Link } from "@/components/ui/link"
import { cn } from "@/lib/utils"

type Props = {
	text: string
	className?: string
}

const URL_PATTERN = /((?:https?:\/\/|www\.)[^\s<]+)/gi

// Sentence punctuation that usually follows a link rather than belonging to it.
const TRAILING_PUNCTUATION = /[.,;:!?'")\]]+$/

const toHref = (url: string): string => {
	const href = url.startsWith("www.") ? `https://${url}` : url

	try {
		const parsed = new URL(href)

		// Links to this site stay in the app.
		return parsed.origin === window.location.origin
			? `${parsed.pathname}${parsed.search}${parsed.hash}`
			: href
	} catch {
		return href
	}
}

/**
 * Plain text with every URL in it turned into a link. Links to this site
 * navigate in the app; anything else opens in a new tab.
 */
export function LinkifiedText({ text, className }: Props) {
	return text.split(URL_PATTERN).map((part, index) => {
		// split() with a capture group puts the matches at odd indexes.
		if (index % 2 === 0) {
			return <Fragment key={index}>{part}</Fragment>
		}

		const trailing = part.match(TRAILING_PUNCTUATION)?.[0] ?? ""
		const url = part.slice(0, part.length - trailing.length)
		const href = toHref(url)
		const external = !href.startsWith("/")

		return (
			<Fragment key={index}>
				<Link
					href={href}
					variant="unstyled"
					target={external ? "_blank" : undefined}
					rel={external ? "noopener noreferrer nofollow" : undefined}
					onClick={(e) => e.stopPropagation()}
					className={cn(
						"break-all text-primary underline underline-offset-4 hover:opacity-80",
						className
					)}>
					{url}
				</Link>
				{trailing}
			</Fragment>
		)
	})
}
