        let slides = document.querySelectorAll('.hero-slide');
        let current = 0;


        setInterval(() => {
            slides[current].classList.remove('active');
            current = (current + 1) % slides.length;
            slides[current].classList.add('active');
        }, 5000);

        const carousel = document.getElementById('teamCarousel');
        const nextBtn = document.getElementById('teamNext');
        const prevBtn = document.getElementById('teamPrev');
        const scrollAmount = 320;

        // Clone isi untuk membuat loop infinite
        const cloneContent = carousel.innerHTML;
        carousel.innerHTML += cloneContent;

        let scrollPos = 0;
        const scrollStep = 1; // Pixel per step
        const scrollInterval = 10; // Delay antar step

        function autoScroll() {
            scrollPos += scrollStep;
            if (scrollPos >= carousel.scrollWidth / 2) {
                scrollPos = 0; // Reset ke awal
            }
            carousel.scrollLeft = scrollPos;
        }

        // Jalankan auto scroll
        setInterval(autoScroll, scrollInterval);

        // Manual next/prev
        nextBtn.addEventListener('click', () => {
            scrollPos += scrollAmount;
            if (scrollPos >= carousel.scrollWidth / 2) {
                scrollPos = 0;
            }
            carousel.scrollLeft = scrollPos;
        });

        prevBtn.addEventListener('click', () => {
            scrollPos -= scrollAmount;
            if (scrollPos < 0) scrollPos = carousel.scrollWidth / 2;
            carousel.scrollLeft = scrollPos;
        });

        // const slides = document.querySelectorAll(".hero-slide");
        const dots = document.querySelectorAll(".dot");
        let currentIndex = 0;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.toggle("active", i === index);
                dots[i].classList.toggle("active", i === index);
            });
            currentIndex = index;
        }

        document.querySelector(".right-arrow").addEventListener("click", () => {
            const nextIndex = (currentIndex + 1) % slides.length;
            showSlide(nextIndex);
        });

        document.querySelector(".left-arrow").addEventListener("click", () => {
            const prevIndex = (currentIndex - 1 + slides.length) % slides.length;
            showSlide(prevIndex);
        });

        dots.forEach(dot => {
            dot.addEventListener("click", () => {
                const index = parseInt(dot.dataset.slide);
                showSlide(index);
            });
        });

        // Optional: Auto slide every 5 seconds
        setInterval(() => {
            const nextIndex = (currentIndex + 1) % slides.length;
            showSlide(nextIndex);
        }, 5000);