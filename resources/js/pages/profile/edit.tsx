import { useQueryClient } from "@tanstack/react-query"
import { ArrowRight, Mic2 } from "lucide-react"
import { useState, type FormEvent } from "react"
import { FileUpload } from "@/components/music/file-upload"
import { PageHeader } from "@/components/social/page-header"
import { Button } from "@/components/ui/button"
import {
	Dialog,
	DialogClose,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
	DialogTrigger,
} from "@/components/ui/dialog"
import { GlassCard } from "@/components/ui/glass-card"
import { Input } from "@/components/ui/input"
import { Link } from "@/components/ui/link"
import { Spinner } from "@/components/ui/spinner"
import { Skeleton } from "@/components/ui/skeleton"
import { useApp } from "@/contexts/AppContext"
import { Head } from "@/lib/spa"
import toast from "@/lib/toast"
import { apiErrorMessage, feedKeys } from "@/queries/feed"
import { socialActions, socialKeys } from "@/queries/social"
import type { User } from "@/types/auth"

const BIO_MAX = 50

/**
 * The music profile: avatar, bio, minimum withdrawal and becoming a
 * musician. Name, email, phone and password live in /settings/profile.
 */
export default function ProfileEdit() {
	const { auth } = useApp()

	if (!auth) {
		return (
			<div className="mx-auto w-full max-w-xl space-y-4 px-4 py-6">
				<Skeleton className="h-10 w-48" />
				<Skeleton className="h-32 w-full rounded-3xl" />
				<Skeleton className="h-48 w-full rounded-3xl" />
			</div>
		)
	}

	return <ProfileEditForm auth={auth} />
}

function ProfileEditForm({ auth }: { auth: User }) {
	const queryClient = useQueryClient()
	const [bio, setBio] = useState(auth?.bio ?? "")
	const [withdrawal, setWithdrawal] = useState("")
	const [saving, setSaving] = useState(false)
	const [upgrading, setUpgrading] = useState(false)
	const [confirmOpen, setConfirmOpen] = useState(false)

	const refreshUser = () => {
		void queryClient.invalidateQueries({ queryKey: ["auth"] })
		void queryClient.invalidateQueries({
			queryKey: socialKeys.profile(auth.username),
		})
		void queryClient.invalidateQueries({ queryKey: feedKeys.posts })
	}

	const onSave = (event: FormEvent) => {
		event.preventDefault()
		setSaving(true)

		socialActions
			.updateUser(auth.id, {
				bio: bio.trim() || undefined,
				withdrawal: withdrawal || undefined,
			})
			.then((res) => {
				toast.success(res.data.message ?? "Account updated")
				setWithdrawal("")
				refreshUser()
			})
			.catch((error) => toast.error(apiErrorMessage(error)))
			.finally(() => setSaving(false))
	}

	const onBecomeMusician = () => {
		setUpgrading(true)

		socialActions
			.updateUser(auth.id, { accountType: "musician" })
			.then(() => {
				toast.success("You're now a musician. Time to upload your first song.")
				setConfirmOpen(false)
				refreshUser()
			})
			.catch((error) => toast.error(apiErrorMessage(error)))
			.finally(() => setUpgrading(false))
	}

	const isMusician = auth.accountType === "musician"

	return (
		<>
			<Head title="Edit profile | Black Music" />
			<div className="mx-auto w-full max-w-xl space-y-4 px-4 py-6">
				<PageHeader
					title="Edit profile"
					backHref={`/profile/show/${auth.username}`}
				/>

				<GlassCard className="space-y-3 p-4 sm:p-5">
					<h2 className="font-semibold">Profile picture</h2>
					<div className="flex items-center gap-4">
						<img
							src={auth.avatar}
							alt={auth.name}
							className="size-20 shrink-0 rounded-full border border-border object-cover"
						/>
						<FileUpload
							endpoint={`avatar/${auth.id}`}
							field="filepond-avatar"
							acceptedFileTypes={["image/*"]}
							maxFileSize="10MB"
							label='Drop a new picture or <span class="filepond--label-action">browse</span>'
							className="min-w-0 flex-1"
							onUploaded={() => {
								toast.success("Profile picture updated")
								refreshUser()
							}}
						/>
					</div>
				</GlassCard>

				<GlassCard className="p-4 sm:p-5">
					<form
						onSubmit={onSave}
						className="space-y-5">
						<div>
							<Input
								label="Bio"
								value={bio}
								maxLength={BIO_MAX}
								onChange={(event) => setBio(event.target.value)}
								helperText={`${bio.length}/${BIO_MAX}`}
							/>
						</div>

						<div className="space-y-2">
							<Input
								label={`Minimum cash withdrawal (now KES ${auth.withdrawal ?? "1000"})`}
								type="number"
								min={0}
								inputMode="numeric"
								value={withdrawal}
								onChange={(event) => setWithdrawal(event.target.value)}
							/>
							<p className="px-1 text-xs text-muted-foreground">
								Setting it below{" "}
								<span className="font-semibold text-primary">KES 1000</span>{" "}
								adds a{" "}
								<span className="font-semibold text-primary">KES 50</span>{" "}
								charge from our provider on each withdrawal.
							</p>
						</div>

						<Button
							type="submit"
							disabled={saving}
							className="w-full">
							{saving ? <Spinner /> : "Save changes"}
						</Button>
					</form>
				</GlassCard>

				{!isMusician && (
					<GlassCard className="space-y-3 p-4 sm:p-5">
						<div className="flex items-start gap-3">
							<span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/15 text-primary">
								<Mic2 className="size-5" />
							</span>
							<div>
								<h2 className="font-semibold">Become a musician</h2>
								<p className="text-sm text-muted-foreground">
									Upload videos and audios, post to your fans and get paid
									when they buy your songs.
								</p>
							</div>
						</div>
						<Dialog
							open={confirmOpen}
							onOpenChange={setConfirmOpen}>
							<DialogTrigger asChild>
								<Button
									variant="outline"
									className="w-full">
									Become a musician
								</Button>
							</DialogTrigger>
							<DialogContent>
								<DialogHeader>
									<DialogTitle>Switch to a musician account?</DialogTitle>
									<DialogDescription>
										This can't be undone. Your account will stay a musician
										account, and we'll set up a "Singles" album for your videos
										and audios.
									</DialogDescription>
								</DialogHeader>
								<DialogFooter>
									<DialogClose asChild>
										<Button variant="ghost">Cancel</Button>
									</DialogClose>
									<Button
										onClick={onBecomeMusician}
										disabled={upgrading}>
										{upgrading ? <Spinner /> : "Yes, make me a musician"}
									</Button>
								</DialogFooter>
							</DialogContent>
						</Dialog>
					</GlassCard>
				)}

				<GlassCard className="p-2">
					<Link
						href="/settings/profile"
						variant="unstyled"
						className="flex items-center justify-between rounded-2xl px-3 py-3 text-sm hover:bg-accent/60">
						<span>
							<span className="block font-semibold">Account settings</span>
							<span className="block text-muted-foreground">
								Name, email, M-Pesa number and password
							</span>
						</span>
						<ArrowRight className="size-4 text-muted-foreground" />
					</Link>
				</GlassCard>
			</div>
		</>
	)
}
