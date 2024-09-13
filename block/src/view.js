document.querySelectorAll('.hm-toc, .hm-table-of-contents').forEach((toc) => {
	const links = toc.querySelectorAll('a');
	const ids = [...links].map(link => new URL(link.href).hash);

	// Cycle backwards through top offsets to get nearest segment.
	const headings = [...document.querySelectorAll( ids.join( ',' ) )].reverse();
	const thresholds = headings.map(heading => heading.getBoundingClientRect().top);
	let currentThreshold = Infinity;

	// Highlight the heading section we are most 'in', regardless of scroll direction.
	window.addEventListener('scroll', () => {
		requestAnimationFrame(() => {
			const scrollTop = document.documentElement.scrollTop;
			const windowHeight = window.innerHeight;
			let threshold = Infinity;

			for (let newThreshold of thresholds) {
				if (newThreshold <= scrollTop + (windowHeight / 2)) {
					threshold = newThreshold;
					break;
				}
			}

			if (threshold === currentThreshold) {
				return;
			}

			currentThreshold = threshold;

			const heading = headings[thresholds.indexOf(currentThreshold)];
			const headingId = heading.getAttribute('id');
			const link = toc.querySelector(`a[href="#${headingId}"]`);

			if (!link) {
				return;
			}

			links.forEach((lnk) => lnk.parentElement.classList.remove('active'));
			link.parentElement.classList.add('active');
		});
	}, { passive: true });
});
