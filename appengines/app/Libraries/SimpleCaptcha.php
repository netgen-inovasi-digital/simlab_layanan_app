<?php

namespace App\Libraries;

/**
 * SimpleCaptcha - Captcha manual mirip Google reCAPTCHA v2 (checkbox)
 * 
 * Menggunakan session token untuk validasi. User hanya perlu mencentang checkbox.
 * Dilengkapi dengan verifikasi AJAX untuk menghasilkan token yang valid.
 * Mendukung multiple captcha instances di halaman yang sama.
 */
class SimpleCaptcha
{
    /**
     * Generate captcha token dan simpan di session (keyed by formId)
     *
     * @param string $key Identifier unik untuk captcha instance
     * @return array ['token' => string, 'timestamp' => int]
     */
    public static function generate(string $key = 'default'): array
    {
        $session = session();
        $token = bin2hex(random_bytes(32));
        $timestamp = time();

        $captchaData = $session->get('captcha_data') ?? [];
        $captchaData[$key] = [
            'token' => $token,
            'time' => $timestamp,
            'verified' => false,
        ];
        $session->set('captcha_data', $captchaData);

        return [
            'token' => $token,
            'timestamp' => $timestamp,
        ];
    }

    /**
     * Verifikasi captcha token dari user
     *
     * @param string $token Token yang dikirim user
     * @return bool
     */
    public static function verify(string $token): bool
    {
        $session = session();
        $captchaData = $session->get('captcha_data') ?? [];

        // Cari token di semua captcha instances
        foreach ($captchaData as $key => $data) {
            if ($data['token'] === $token) {
                // Cek apakah sudah di-verify via AJAX
                if (!$data['verified']) {
                    return false;
                }

                // Cek expired (5 menit)
                if (time() - $data['time'] > 300) {
                    self::clearKey($key);
                    return false;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Mark captcha as verified (dipanggil saat user mencentang checkbox via AJAX)
     *
     * @param string $token
     * @return bool
     */
    public static function markVerified(string $token): bool
    {
        $session = session();
        $captchaData = $session->get('captcha_data') ?? [];

        foreach ($captchaData as $key => &$data) {
            if ($data['token'] === $token) {
                // Cek expired
                if (time() - $data['time'] > 300) {
                    self::clearKey($key);
                    return false;
                }

                $data['verified'] = true;
                $session->set('captcha_data', $captchaData);
                return true;
            }
        }

        return false;
    }

    /**
     * Hapus captcha tertentu dari session
     */
    public static function clearKey(string $key): void
    {
        $session = session();
        $captchaData = $session->get('captcha_data') ?? [];
        unset($captchaData[$key]);
        $session->set('captcha_data', $captchaData);
    }

    /**
     * Hapus semua captcha dari session
     */
    public static function clear(): void
    {
        $session = session();
        $session->remove('captcha_data');
    }

    /**
     * Render captcha widget HTML
     *
     * @param string $formId ID form yang menggunakan captcha 
     * @return string HTML captcha widget
     */
    public static function render(string $formId = 'captcha-form'): string
    {
        $captchaData = self::generate($formId);
        $token = $captchaData['token'];

        $logoUrl = base_url('assets/img/recaptcha.png');

        return '
        <div class="simple-captcha-widget mb-3" id="captcha-widget-' . $formId . '">
            <div class="captcha-box" id="captcha-box-' . $formId . '">
                <div class="captcha-left">
                    <div class="captcha-checkbox" id="captcha-cb-' . $formId . '" role="checkbox" aria-checked="false" tabindex="0">
                        <div class="captcha-spinner" id="captcha-spinner-' . $formId . '" style="display:none;">
                            <div class="spinner-anim"></div>
                        </div>
                        <div class="captcha-checkmark" id="captcha-check-' . $formId . '" style="display:none;">
                            <svg viewBox="0 0 24 24" width="36" height="36">
                                <path fill="none" stroke="#4CAF50" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M4 13l5 5L20 6"/>
                            </svg>
                        </div>
                    </div>
                    <label class="captcha-label">I\'m not a robot</label>
                </div>
                <div class="captcha-right">
                    <img src="' . $logoUrl . '" alt="reCAPTCHA" class="captcha-logo" />
                    <span class="captcha-brand-text">reCAPTCHA</span>
                </div>
            </div>
            <input type="hidden" name="captcha_token" id="captcha-token-' . $formId . '" value="' . $token . '" />
            <input type="hidden" name="captcha_verified" id="captcha-verified-' . $formId . '" value="0" />
            <div class="captcha-error text-danger small mt-1" id="captcha-error-' . $formId . '" style="display:none;">
                Silakan centang captcha terlebih dahulu.
            </div>
        </div>

        <script>
        (function() {
            var formId = "' . $formId . '";
            var checkbox = document.getElementById("captcha-cb-" + formId);
            var spinner = document.getElementById("captcha-spinner-" + formId);
            var checkmark = document.getElementById("captcha-check-" + formId);
            var tokenInput = document.getElementById("captcha-token-" + formId);
            var verifiedInput = document.getElementById("captcha-verified-" + formId);
            var errorDiv = document.getElementById("captcha-error-" + formId);
            var isVerified = false;

            if (!checkbox) return;

            checkbox.addEventListener("click", function() {
                if (isVerified) return;
                
                // Tampilkan spinner
                spinner.style.display = "block";
                errorDiv.style.display = "none";
                checkbox.classList.add("captcha-loading");

                // Simulasi verifikasi (delay 800-1500ms seperti reCAPTCHA)
                var delay = 800 + Math.random() * 700;
                
                // Kirim AJAX untuk verifikasi token
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "' . base_url('captcha/verify') . '", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
                
                // Ambil CSRF token dari form terdekat
                var widget = document.getElementById("captcha-widget-" + formId);
                var form = widget ? widget.closest("form") : null;
                var csrfToken = "";
                var csrfKey = "";
                if (form) {
                    // Cari input CSRF (hidden input dengan name yang mengandung csrf)
                    var allInputs = form.querySelectorAll("input[type=hidden]");
                    for (var i = 0; i < allInputs.length; i++) {
                        if (allInputs[i].name.indexOf("csrf") !== -1) {
                            csrfKey = allInputs[i].name;
                            csrfToken = allInputs[i].value;
                            break;
                        }
                    }
                }
                // Fallback: cari csrf dari semua form di halaman
                if (!csrfKey) {
                    var allPageInputs = document.querySelectorAll("input[type=hidden]");
                    for (var i = 0; i < allPageInputs.length; i++) {
                        if (allPageInputs[i].name.indexOf("csrf") !== -1) {
                            csrfKey = allPageInputs[i].name;
                            csrfToken = allPageInputs[i].value;
                            break;
                        }
                    }
                }
                // Final fallback: gunakan csrf_token() dari CI
                if (!csrfKey) {
                    csrfKey = "' . csrf_token() . '";
                    var csrfMeta = document.querySelector("meta[name=X-CSRF-TOKEN]");
                    if (csrfMeta) csrfToken = csrfMeta.getAttribute("content");
                }
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        setTimeout(function() {
                            spinner.style.display = "none";
                            checkbox.classList.remove("captcha-loading");
                            
                            if (xhr.status === 200) {
                                try {
                                    var resp = JSON.parse(xhr.responseText);
                                    if (resp.status === "success") {
                                        checkmark.style.display = "flex";
                                        checkbox.classList.add("captcha-verified");
                                        checkbox.setAttribute("aria-checked", "true");
                                        verifiedInput.value = "1";
                                        isVerified = true;
                                        
                                        // Update CSRF token di semua form di halaman
                                        if (resp.csrf_token && resp.csrf_name) {
                                            var csrfInputs = document.querySelectorAll("input[name=\'" + resp.csrf_name + "\']");
                                            csrfInputs.forEach(function(input) {
                                                input.value = resp.csrf_token;
                                            });
                                        }
                                    } else {
                                        errorDiv.textContent = resp.message || "Verifikasi gagal. Silakan muat ulang halaman.";
                                        errorDiv.style.display = "block";
                                    }
                                } catch(e) {
                                    errorDiv.textContent = "Verifikasi gagal. Silakan muat ulang halaman.";
                                    errorDiv.style.display = "block";
                                }
                            } else {
                                errorDiv.textContent = "Verifikasi gagal. Silakan muat ulang halaman.";
                                errorDiv.style.display = "block";
                            }
                        }, delay);
                    }
                };
                
                xhr.send("captcha_token=" + encodeURIComponent(tokenInput.value) + "&" + csrfKey + "=" + encodeURIComponent(csrfToken));
            });

            // Keyboard accessibility
            checkbox.addEventListener("keydown", function(e) {
                if (e.key === "Enter" || e.key === " ") {
                    e.preventDefault();
                    checkbox.click();
                }
            });

            // Form submit validation
            var widget = document.getElementById("captcha-widget-" + formId);
            var form = widget ? widget.closest("form") : null;
            if (form) {
                form.addEventListener("submit", function(e) {
                    if (!isVerified) {
                        e.preventDefault();
                        errorDiv.style.display = "block";
                        widget.scrollIntoView({ behavior: "smooth", block: "center" });
                    }
                });
            }
        })();
        </script>
        ';
    }
}
