import { useQueryClient } from "@tanstack/react-query"
import { useNavigate } from "@tanstack/react-router"
import { useState } from "react"
import { useApp } from "@/contexts/AppContext"
import toast from "@/lib/toast"
import { apiErrorMessage } from "@/queries/feed"
import { videoActions, videoKeys } from "@/queries/video"

/**
 * Sends guests to the login page (music.black's LoginPopUp) and returns
 * false, or returns true for signed-in users.
 */
export function useRequireAccount() {
	const { auth } = useApp()
	const navigate = useNavigate()

	return (message = "Sign in to continue") => {
		if (auth) {
			return true
		}

		toast.info(message)
		void navigate({ to: "/login" as never })

		return false
	}
}

/**
 * Add-to-cart and buy (KES 20) for one video. Toggling is optimistic and
 * reverts on error; buying adds the video if needed and opens the cart.
 */
export function useVideoCart(videoId: string, initialInCart: boolean) {
	const navigate = useNavigate()
	const queryClient = useQueryClient()
	const requireAccount = useRequireAccount()
	const [inCart, setInCart] = useState(initialInCart)

	const toggle = () => {
		const previous = inCart
		setInCart(!previous)

		return videoActions
			.cart(videoId)
			.then((res) => {
				if (res.data.message) {
					toast.success(res.data.message)
				}
				void queryClient.invalidateQueries({ queryKey: videoKeys.all })
			})
			.catch((error) => {
				setInCart(previous)
				toast.error(apiErrorMessage(error))
				throw error
			})
	}

	const onToggle = () => {
		if (requireAccount("Sign in to add videos to your cart")) {
			toggle().catch(() => undefined)
		}
	}

	const onBuy = () => {
		if (!requireAccount("Sign in to buy videos")) {
			return
		}

		const added = inCart ? Promise.resolve() : toggle()

		added
			.then(() => navigate({ to: "/cart" as never }))
			.catch(() => undefined)
	}

	return { inCart, onToggle, onBuy }
}
