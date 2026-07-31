// When pressing any submit button an any form, disable them.
// This prevents multiple submission and provides feedback to the user.
function onSubmitDisableInputs() {
	let forms = document.getElementsByTagName("form");

	for (let form of forms) {
		form.addEventListener("submit", (event) => {
			let submits = form.querySelectorAll("input[type=submit]");

			for (let submit of submits) {
				submit.disabled = true;
			};
		});
	}
}

// Place tooltip next to the item, rather than to the lower right.
function adjustTooltipPos() {
	let schedule = document.getElementById("schedule");

	if (schedule) {
		$(document).tooltip({
			position: {
				my: "left top",
				at: "right top",
				collision: "flipfit"
			}
		});
	}
}

// Toggle watchlist from menu
function onToggleWatchlist() {
	let toggle = document.getElementById("toggle-watchlist");

	if (toggle) {
		toggle.addEventListener("click", (event) => {
			toggleWatchlist();
			event.stopPropagation();
		});
	}
}

(function() {
	onSubmitDisableInputs();
	onToggleWatchlist();
	adjustTooltipPos();
})();
