(() => {
	const root = document.documentElement;
	const themeButton = document.querySelector(".theme-toggle");
	const savedTheme = localStorage.getItem("community-voice-theme");
	const preferredTheme = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";

	function setTheme(theme) {
		root.dataset.theme = theme;
		if (themeButton) {
			const nextTheme = theme === "dark" ? "light" : "dark";
			themeButton.setAttribute("aria-label", `Switch to ${nextTheme} mode`);
			themeButton.title = `Switch to ${nextTheme} mode`;
			themeButton.querySelector(".theme-icon").textContent = theme === "dark" ? "☼" : "◐";
		}
	}

	setTheme(savedTheme || preferredTheme);
	themeButton?.addEventListener("click", () => {
		const nextTheme = root.dataset.theme === "dark" ? "light" : "dark";
		localStorage.setItem("community-voice-theme", nextTheme);
		setTheme(nextTheme);
	});

	const menuButton = document.querySelector(".menu-toggle");
	const nav = document.querySelector(".main-nav");
	menuButton?.addEventListener("click", () => {
		const isOpen = menuButton.getAttribute("aria-expanded") === "true";
		menuButton.setAttribute("aria-expanded", String(!isOpen));
		menuButton.setAttribute("aria-label", isOpen ? "Open navigation" : "Close navigation");
		nav?.classList.toggle("is-open", !isOpen);
	});
})();
