const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const contentOverlay = document.getElementById('content-overlay');
const containerFluid = document.querySelector('.container-fluid');
const navbar = document.querySelector('.navbar');
let currentUrl = '';

// Fungsi untuk menampilkan atau menyembunyikan sidebar
function toggleSidebar() {
    sidebar.classList.toggle('sidebar-hide');
    containerFluid.classList.toggle('content-fullwidth');
    navbar.classList.toggle('fullwidth');  // Toggle class fullwidth pada navbar
}

// Event listener untuk tombol toggle sidebar
sidebarToggle.addEventListener('click', () => {
    if (window.innerWidth < 768) {
        // Jika layar lebih kecil dari 768px (mobile), tampilkan sidebar dengan overlay
        if (sidebar.classList.contains('sidebar-show')) {
            sidebar.classList.remove('sidebar-show');
            contentOverlay.classList.remove('show');
            navbar.classList.remove('fullwidth'); // Navbar kembali ke posisi normal
        } else {
            sidebar.classList.add('sidebar-show');
            contentOverlay.classList.add('show');
            navbar.classList.add('fullwidth'); // Navbar fullwidth saat sidebar disembunyikan
        }
    } else {
        // Untuk layar besar, sembunyikan sidebar tanpa overlay
        toggleSidebar();
    }
});

// Event listener untuk klik di overlay
contentOverlay.addEventListener('click', () => {
    sidebar.classList.remove('sidebar-show');
    contentOverlay.classList.remove('show');
    navbar.classList.add('fullwidth');  // Navbar fullwidth saat overlay ditutup di mobile
});

// Event listener untuk layar di-resize
window.addEventListener('resize', () => {
    if (window.innerWidth >= 768) {
        sidebar.classList.remove('sidebar-show');
        contentOverlay.classList.remove('show');
        containerFluid.classList.remove('content-fullwidth');
        navbar.classList.remove('fullwidth');
    }
});

// Ambil semua toggle menu
const toggles = document.querySelectorAll('.nav-link[id$="Toggle"]');

//Tambahkan event listener untuk setiap toggle
toggles.forEach(toggle => {
    toggle.addEventListener('click', (e) => {
        e.preventDefault(); // Mencegah default link behavior

        // Ambil submenu yang terkait
        const submenu = toggle.nextElementSibling;

        // Toggle submenu yang terkait
        submenu.classList.toggle('show');

        // Ganti icon caret
        const caret = toggle.querySelector('.caret');
        caret.classList.toggle('bi-chevron-right');
        caret.classList.toggle('bi-chevron-down');

        // Menutup submenu lainnya jika ingin hanya satu yang terbuka
        toggles.forEach(otherToggle => {
            if (otherToggle !== toggle) {
                const otherSubmenu = otherToggle.nextElementSibling;
                otherSubmenu.classList.remove('show'); // Sembunyikan submenu lainnya
                const otherCaret = otherToggle.querySelector('.caret');
                otherCaret.classList.remove('bi-chevron-down');
                otherCaret.classList.add('bi-chevron-right');
            }
        });
    });
});

function loadContent(page) {
    const targetElement = document.getElementById('content');
    if (!targetElement) {
        console.error('Target element not found');
        return;
    }
    // Clear the target element before loading new content
    targetElement.innerHTML = '';

    fetch(page)
    .then(response => {
        if (!response.ok) {
            targetElement.innerHTML = `Page not found (${response.status})`;
            throw new Error(`Failed to load page: ${response.status}`);
        } return response.text();
    })
    .then(data => {
		if (data.includes('<!--LOGIN_PAGE_MARKER-->')) {
            window.location.href = './login';
            return;
        }
        targetElement.innerHTML = data;
        // Process inline scripts in the loaded content
        const scripts = targetElement.querySelectorAll("script");
        scripts.forEach(script => {
            const newScript = document.createElement("script");
            if (script.src) {
                newScript.src = script.src;
            } else {
                newScript.textContent = script.textContent;
            }
            document.body.appendChild(newScript);
            newScript.remove?.();
        });
    })
    .catch(error => {
        console.error('Error loading content:', error);
        targetElement.innerHTML = `<div style="color:red;">Failed to load content.</div>`;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(event) {
			const isCollapseToggle = link.getAttribute('data-bs-toggle') === 'collapse';

            if (!isCollapseToggle) {
                event.preventDefault();
				document.querySelectorAll('.nav-link').forEach(item => {
					item.classList.remove('active');
				});
				link.classList.add('active');
				const page = link.getAttribute('href');
				loadContent(page);
				firstLoad = true;
				currentUrl = page;
				
				if (window.innerWidth < 768) {
					if (sidebar.classList.contains('sidebar-show')) {
						sidebar.classList.remove('sidebar-show');
						contentOverlay.classList.remove('show');
					}
				}
			}
        });
    });
});


