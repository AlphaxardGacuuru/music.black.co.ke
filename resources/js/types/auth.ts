export type UserSettings = {
	installOnboardedAt?: string
	pwaInstalledAt?: string
	permissionsOnboardedAt?: string
	referralOnboardedAt?: string
	referralSignupNotification?: boolean
	[key: string]: unknown
}

export type User = {
	id: string
	name: string
	/** Public handle, e.g. "@blackmusic". */
	username: string
	email: string
	phone: string | null
	avatar?: string
	verified?: boolean
	email_verified_at: string | null
	twoFactorEnabled?: boolean
	referralsCount?: number
	settings?: UserSettings | null
	// Music profile (UserResource).
	accountType?: "normal" | "musician"
	backdrop?: string
	bio?: string | null
	dob?: string | null
	location?: string | null
	/** The balance a musician must reach before withdrawing, in KES. */
	withdrawal?: string
	posts?: number
	following?: number
	fans?: number
	hasFollowed?: boolean
	hasBought1?: boolean
	decos?: number
	/** Wallet balance for buying songs, in KES. */
	balance?: number
	videos?: number
	boughtVideos?: number
	audios?: number
	boughtAudios?: number
	created_at: string
	updated_at: string
	[key: string]: unknown
}

export type TwoFactorSetupData = {
	svg: string
	url: string
}

export type TwoFactorSecretKey = {
	secretKey: string
}
