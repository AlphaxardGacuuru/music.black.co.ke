import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"
import Axios from "@/lib/axios"
import type { UserSettings } from "@/types/auth"

export type AdminDashboardData = {
	totals: {
		totalUsers: number
		totalMusicians: number
		totalVideos: number
		totalAudios: number
		totalSales: number
	}
	dailyVolume: { date: string; sold: number }[]
}

export function useAdminDashboard() {
	return useQuery({
		queryKey: ["admin", "dashboard"],
		queryFn: () =>
			Axios.get<{ data: AdminDashboardData }>("api/admin/dashboard").then(
				(res) => res.data.data
			),
	})
}

export type AdminUser = {
	id: string
	name: string
	email: string
	phone: string | null
	gender: "male" | "female" | "other" | null
	avatar: string | null
	verified: boolean
	settings: UserSettings | null
	pushSubscriptionsCount: number
	createdAt: string
}

type AdminUsersResponse = {
	data: AdminUser[]
	meta: { current_page: number; last_page: number; total: number }
}

export function useAdminUsers(
	search: string,
	page = 1,
	perPage = 20,
	enabled = true
) {
	return useQuery({
		enabled,
		queryKey: ["admin", "users", search, page, perPage],
		queryFn: () =>
			Axios.get<AdminUsersResponse>("api/admin/users", {
				params: { name: search || undefined, page, per_page: perPage },
			}).then((res) => res.data),
	})
}

export function useToggleUserVerified() {
	const queryClient = useQueryClient()

	return useMutation({
		mutationFn: ({ userId, verified }: { userId: string; verified: boolean }) =>
			Axios.patch(`api/admin/users/${userId}/verify`, { verified }),
		onSuccess: () => {
			queryClient.invalidateQueries({ queryKey: ["admin", "users"] })
		},
	})
}

export type AdminKopokopoTransfer = {
	id: string
	user: string | null
	kopokopoId: string | null
	kopokopoCreatedAt: string | null
	amount: string
	currency: string | null
	status: string | null
	errors: unknown
	transferBatches: unknown
	metadata: unknown
	createdAt: string
}

type AdminKopokopoTransfersResponse = {
	data: AdminKopokopoTransfer[]
	meta: { current_page: number; last_page: number; total: number }
}

export function useAdminKopokopoTransfers(page = 1, perPage = 20) {
	return useQuery({
		queryKey: ["admin", "kopokopo-transfers", page, perPage],
		queryFn: () =>
			Axios.get<AdminKopokopoTransfersResponse>(
				"api/admin/kopokopo-transfers",
				{ params: { page, per_page: perPage } }
			).then((res) => res.data),
	})
}

export function useSendKopokopoTransfer() {
	const queryClient = useQueryClient()

	return useMutation({
		// The endpoint always responds 200, even on failure — Kopokopo/M-Pesa
		// errors surface in the body's `status`/`message`, not the HTTP
		// status, so a non-success body is turned into a rejected promise
		// here to fit react-query's normal onSuccess/onError handling.
		mutationFn: (payload: {
			recipientName: string
			destinationReference: string
			amount: number
			description?: string
		}) =>
			Axios.post<{ status: unknown; message: string }>(
				"api/admin/kopokopo-transfers/initiate",
				payload
			).then((res) => {
				if (res.data.status !== true) {
					throw new Error(res.data.message)
				}

				return res.data
			}),
		onSuccess: () => {
			queryClient.invalidateQueries({
				queryKey: ["admin", "kopokopo-transfers"],
			})
		},
	})
}

export type KopokopoRecipientType =
	| "mobile_wallet"
	| "bank_account"
	| "till"
	| "paybill"

export type AdminKopokopoRecipient = {
	id: string
	type: KopokopoRecipientType
	firstName: string | null
	lastName: string | null
	email: string | null
	phoneNumber: string | null
	accountName: string | null
	accountNumber: string | null
	tillName: string | null
	tillNumber: string | null
	paybillName: string | null
	paybillNumber: string | null
	paybillAccountNumber: string | null
	description: string | null
}

export function useAdminKopokopoRecipients() {
	return useQuery({
		queryKey: ["admin", "kopokopo-recipients"],
		queryFn: () =>
			Axios.get<{ data: AdminKopokopoRecipient[] }>(
				"api/admin/kopokopo-recipients"
			).then((res) => res.data.data),
	})
}

export type AddKopokopoRecipientPayload = {
	type: KopokopoRecipientType
	description: string
	firstName?: string
	lastName?: string
	email?: string
	phoneNumber?: string
	accountName?: string
	accountNumber?: string
	bankBranchRef?: string
	tillName?: string
	tillNumber?: string
	paybillName?: string
	paybillNumber?: string
	paybillAccountNumber?: string
}

export function useAddKopokopoRecipient() {
	const queryClient = useQueryClient()

	return useMutation({
		// Same status-in-body convention as useSendKopokopoTransfer above.
		mutationFn: (payload: AddKopokopoRecipientPayload) =>
			Axios.post<{ status: unknown; message: string }>(
				"api/admin/kopokopo-recipients",
				payload
			).then((res) => {
				if (res.data.status !== true) {
					throw new Error(res.data.message)
				}

				return res.data
			}),
		onSuccess: () => {
			queryClient.invalidateQueries({
				queryKey: ["admin", "kopokopo-recipients"],
			})
		},
	})
}
