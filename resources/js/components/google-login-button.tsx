import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"

type Props = {
	href?: string
}

export default function GoogleLoginButton({
	href = "login/google/redirect",
}: Props) {
	const [loading, setLoading] = useState(false)

	return (
		<Button
			type="button"
			variant="transparent"
			className="w-full"
			disabled={loading}
			asChild>
			<a
				href={href}
				onClick={(event) => {
					if (loading) {
						event.preventDefault()

						return
					}

					setLoading(true)
				}}
				className={loading ? "pointer-events-none" : undefined}
				aria-disabled={loading}>
				{loading ? (
					<>
						<Spinner />
						Redirecting to Google...
					</>
				) : (
					<>
						<svg
							aria-hidden="true"
							className="size-4"
							viewBox="0 0 24 24">
							<path
								fill="#4285F4"
								d="M23.49 12.27c0-.79-.07-1.54-.2-2.27H12v4.3h6.44a5.5 5.5 0 0 1-2.39 3.61v3h3.86c2.25-2.07 3.58-5.12 3.58-8.64Z"
							/>
							<path
								fill="#34A853"
								d="M12 24c3.24 0 5.96-1.08 7.95-2.92l-3.86-3c-1.08.72-2.46 1.14-4.09 1.14-3.14 0-5.8-2.12-6.75-4.97H1.26v3.1A12 12 0 0 0 12 24Z"
							/>
							<path
								fill="#FBBC05"
								d="M5.25 14.25A7.2 7.2 0 0 1 4.87 12c0-.78.14-1.53.38-2.25v-3.1H1.26A12 12 0 0 0 0 12c0 1.93.46 3.76 1.26 5.35l3.99-3.1Z"
							/>
							<path
								fill="#EA4335"
								d="M12 4.78c1.76 0 3.34.61 4.58 1.82l3.43-3.43C17.95 1.25 15.23 0 12 0A12 12 0 0 0 1.26 6.65l3.99 3.1c.94-2.85 3.61-4.97 6.75-4.97Z"
							/>
						</svg>
						Continue with Google
					</>
				)}
			</a>
		</Button>
	)
}
