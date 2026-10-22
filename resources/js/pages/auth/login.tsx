import { Head } from "@/lib/spa"
import { useState, useEffect } from "react"
import { useNavigate } from "@tanstack/react-router"
import { LoaderCircle } from "lucide-react"
import GoogleLoginButton from "@/components/google-login-button"
import InputError from "@/components/input-error"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Link } from "@/components/ui/link"
import { useApp } from "@/contexts/AppContext"
import axios from "@/lib/axios"
import toast from "@/lib/toast"
import { invalidateAuth } from "@/middleware/auth"
import { register } from "@/routes"
import { store } from "@/routes/login"
import { request } from "@/routes/password"

type Props = {
	status?: string
	googleLoginUrl: string
}

export default function Login({
	status,
	googleLoginUrl = "login/google/redirect",
}: Props) {
	const { setLocalStorage } = useApp()
	const navigate = useNavigate()
	const searchParams = new URLSearchParams(window.location.search)
	const oauthError = searchParams.get("error")

	const [processing, setProcessing] = useState(false)
	const [errors, setErrors] = useState<Record<string, string>>({})

	useEffect(() => {
		if (oauthError) {
			toast.error(oauthError)
		}
	}, [oauthError])

	function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
		event.preventDefault()
		setProcessing(true)
		setErrors({})
		const fd = new FormData(event.currentTarget)

		const { url, method } = store()

		axios
			.request({
				url,
				method,
				data: { ...Object.fromEntries(fd), device_name: "web" },
			})
			.then((response) => {
				if (response.data.two_factor) {
					navigate({
						to: "/two-factor-challenge",
						search: { pending_token: response.data.data },
					})
					return
				}

				setLocalStorage("sanctumToken", response.data.data)
				invalidateAuth()
				toast.success(response.data.message ?? "Logged in")
				navigate({ to: "/" })
			})
			.catch((err: unknown) => {
				const e = err as {
					response?: {
						status?: number
						data?: { errors?: Record<string, string | string[]> }
					}
				}

				if (e.response?.status === 422) {
					const raw = e.response.data?.errors ?? {}
					setErrors(
						Object.fromEntries(
							Object.entries(raw).map(([k, v]) => [
								k,
								Array.isArray(v) ? String(v[0] ?? "") : String(v),
							])
						)
					)
				} else if (e.response?.status === 429) {
					toast.error("Too many attempts. Please wait a minute and try again.")
				}
			})
			.finally(() => {
				setProcessing(false)
			})
	}

	return (
		<>
			<Head title="Log in" />

			<div className="flex flex-col gap-6">
				<GoogleLoginButton href={googleLoginUrl} />

				<div className="flex items-center gap-3 text-xs text-muted-foreground">
					<span className="h-px flex-1 bg-border" />
					or
					<span className="h-px flex-1 bg-border" />
				</div>

				<form
					onSubmit={handleSubmit}
					className="grid gap-4">
					<div className="grid gap-2">
						<Input
							id="email"
							label="Email"
							type="email"
							name="email"
							autoComplete="email"
							autoFocus
							required
						/>
						<InputError message={errors.email} />
					</div>

					<div className="grid gap-2">
						<Input
							id="password"
							label="Password"
							type="password"
							name="password"
							autoComplete="current-password"
							required
						/>
						<InputError message={errors.password} />
					</div>

					<div className="text-right text-sm">
						<Link
							href={request().url}
							variant="text"
							size="none">
							Forgot password?
						</Link>
					</div>

					<Button
						className="w-full"
						disabled={processing}>
						{processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
						Log in
					</Button>
				</form>

				<div className="space-x-1 text-center text-sm text-muted-foreground">
					<span>New to Black Music?</span>
					<Link
						href={register().url}
						variant="text"
						size="none">
						Create an account
					</Link>
				</div>
			</div>

			{status && (
				<div className="mb-4 text-center text-sm font-medium text-green-600">
					{status}
				</div>
			)}
		</>
	)
}

Login.layout = {
	title: "Log in to your account",
	description: "Continue with Google, or use your email and password",
}
