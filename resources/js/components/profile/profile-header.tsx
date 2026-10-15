import { useNavigate } from "@tanstack/react-router"
import { useQueryClient } from "@tanstack/react-query"
import { Award, Check, MessageCircle, PenLine } from "lucide-react"
import { useState } from "react"
import { AvatarPreviewDialog } from "@/components/avatar-preview-dialog"
import { FeedImage } from "@/components/feed/feed-image"
import { Button } from "@/components/ui/button"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import VerifiedBadge from "@/components/verified-badge"
import { useApp } from "@/contexts/AppContext"
import { useInitials } from "@/hooks/use-initials"
import toast from "@/lib/toast"
import { apiErrorMessage, feedKeys } from "@/queries/feed"
import { socialActions, socialKeys } from "@/queries/social"
import type { Profile } from "@/types/social"

/**
 * Backdrop, avatar, name, decos, bio and counts, with Edit (own profile) or
 * Follow and Message. Fans can only follow a musician after buying one of
 * their songs; the API explains when that's not the case.
 */
export function ProfileHeader({ user }: { user: Profile }) {
	const { auth } = useApp()
	const navigate = useNavigate()
	const queryClient = useQueryClient()
	const getInitials = useInitials()
	const [hasFollowed, setHasFollowed] = useState(user.hasFollowed)
	const [following, setFollowing] = useState(false)

	const isOwn = auth?.username === user.username
	const canBeFollowed = !isOwn && user.username !== "@blackmusic"

	const onFollow = () => {
		if (!auth) {
			toast.error("Sign in to follow musicians")
			void navigate({ to: "/login" })

			return
		}

		if (!user.hasBought1 && auth.username !== "@blackmusic") {
			toast.error(`You must have bought at least one song by ${user.username}`)

			return
		}

		setHasFollowed(!hasFollowed)
		setFollowing(true)

		socialActions
			.follow(user.username)
			.then((res) => {
				const message = res.data.message ?? ""

				// The API answers 200 with an explanation when following isn't allowed
				if (message.startsWith("You must")) {
					setHasFollowed(hasFollowed)
					toast.error(message)

					return
				}

				if (message) {
					toast.success(message)
				}

				void queryClient.invalidateQueries({
					queryKey: socialKeys.profile(user.username),
				})
				void queryClient.invalidateQueries({
					queryKey: socialKeys.profilePosts(user.username),
				})
				void queryClient.invalidateQueries({ queryKey: feedKeys.posts })
				void queryClient.invalidateQueries({ queryKey: feedKeys.stories })
				void queryClient.invalidateQueries({ queryKey: feedKeys.artists })
			})
			.catch((error) => {
				setHasFollowed(hasFollowed)
				toast.error(apiErrorMessage(error))
			})
			.finally(() => setFollowing(false))
	}

	const stats = [
		{ label: "Posts", value: user.posts },
		{ label: "Following", value: Math.max(0, user.following) },
		{ label: "Fans", value: user.fans },
	]

	return (
		<GlassCard className="overflow-hidden">
			{/* Backdrop */}
			<div className="relative h-36 bg-linear-to-br from-primary/40 via-primary/10 to-transparent sm:h-52">
				<FeedImage
					src={user.backdrop}
					className="absolute inset-0 size-full object-cover"
				/>
			</div>

			<div className="px-4 pb-5 sm:px-6">
				<div className="-mt-12 flex items-end justify-between gap-3 sm:-mt-14">
					<AvatarPreviewDialog
						src={user.avatar}
						alt={user.name}
						fallback={getInitials(user.name)}
						verified={user.verified}
						className="size-24 border-4 border-background text-2xl sm:size-28"
						fallbackClassName="bg-primary/15 text-primary"
					/>

					<div className="flex flex-wrap justify-end gap-2 pb-1">
						{isOwn && (
							<Link
								href="/settings/profile"
								variant="outline"
								size="sm"
								className="gap-1.5">
								<PenLine className="size-4" />
								Edit profile
							</Link>
						)}
						{!isOwn && auth && (
							<Link
								href={`/chat/${user.username}/view`}
								variant="outline"
								size="sm"
								className="gap-1.5">
								<MessageCircle className="size-4" />
								Message
							</Link>
						)}
						{canBeFollowed && user.accountType === "musician" && (
							<Button
								size="sm"
								variant={hasFollowed ? "secondary" : "default"}
								disabled={following}
								onClick={onFollow}
								className="gap-1.5 px-4">
								{hasFollowed ? (
									<>
										Followed
										<Check className="size-4" />
									</>
								) : (
									"Follow"
								)}
							</Button>
						)}
					</div>
				</div>

				<div className="mt-3">
					<h1 className="flex items-center gap-1.5 text-2xl font-bold">
						<span className="truncate">{user.name}</span>
						{user.verified && <VerifiedBadge className="size-5" />}
					</h1>
					<p className="flex flex-wrap items-center gap-x-2 text-sm text-muted-foreground">
						{user.username}
						<span className="inline-flex items-center gap-0.5 font-medium text-primary">
							<Award className="size-4" />
							{user.decos}
						</span>
						{user.accountType === "musician" && (
							<span className="rounded-full bg-primary/15 px-2 py-0.5 text-[11px] font-medium text-primary">
								Musician
							</span>
						)}
					</p>
					{user.bio && <p className="mt-2 text-[15px]">{user.bio}</p>}
				</div>

				<div className="mt-4 flex gap-6">
					{stats.map((stat) => (
						<div key={stat.label}>
							<p className="text-lg font-bold tabular-nums">{stat.value}</p>
							<p className="text-xs text-muted-foreground">{stat.label}</p>
						</div>
					))}
				</div>
			</div>
		</GlassCard>
	)
}
