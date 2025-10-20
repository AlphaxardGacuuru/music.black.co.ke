import React, { useEffect } from "react"
import { useParams } from "react-router-dom"
import CryptoJS from "crypto-js"

const Socialite = (props) => {
	let { message, token } = useParams()

	useEffect(() => {
		props.setMessages([message])

		// Encrypt Token
		const encryptedToken = (token) => {
			const secretKey = "BlackMusicAuthorizationToken"
			// Encrypt
			return CryptoJS.AES.encrypt(token, secretKey).toString()
		}

		// Encrypt and Save Sanctum Token to Local Storage
		props.setLocalStorage("sanctumToken", encryptedToken(token))

		// Redirect to index page
		setTimeout(() => window.location.href = "/", 3000)
	}, [])

	return (
		<div
			id="preloader"
			style={{
				backgroundImage: `url("/storage/img/Banner-3.jpg")`,
				backgroundPosition: "top",
				backgroundSize: "cover",
			}}>
			<center className="bg-dark bg-opacity-75 p-5">
				<h1 className="text-white mb-5">Welcome to Black Music</h1>
				<div id="sonar-load"></div>
				<div className="text-white mt-5">Redirecting...</div>
			</center>
		</div>
	)
}

export default Socialite
