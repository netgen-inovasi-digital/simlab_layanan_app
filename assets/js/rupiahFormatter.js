/**
 * JavaScript utility functions for rupiah formatting
 * Use this file for consistent currency formatting across the application
 */

// Format rupiah using modern Intl.NumberFormat API
function formatRupiahIntl(harga) {
    return new Intl.NumberFormat("id", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(harga);
}

// Legacy format rupiah function for compatibility
function formatRupiah(angka, mataUang = true) {
    let rupiah = angka.replace(/[^,\d]/g, '').toString();
    let split = rupiah.split(',');
    let sisa = split[0].length % 3;
    let rupiahFormatted = split[0].substr(0, sisa);
    let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiahFormatted += separator + ribuan.join('.');
    }

    rupiahFormatted = split[1] != undefined ? rupiahFormatted + ',' + split[1] : rupiahFormatted;

    return mataUang ? 'Rp ' + rupiahFormatted : rupiahFormatted;
}

// Parse formatted rupiah back to number
function parseRupiah(rupiahString) {
    let angka = rupiahString.replace(/[^,\d]/g, '');
    angka = angka.replace(/\./g, '');
    angka = angka.replace(',', '.');
    return parseFloat(angka) || 0;
}

// Auto-format input fields with rupiah formatting
function initRupiahInputs() {
    // Untuk elemen dengan class .rupiah-input
    document.querySelectorAll('.rupiah-input').forEach(function(el) {
        el.addEventListener('keyup', function(e) {
            this.value = formatRupiah(this.value, true);
        });
        
        el.addEventListener('focus', function(e) {
            // Remove formatting on focus for easier editing
            this.value = this.value.replace(/[^,\d]/g, '');
        });
        
        el.addEventListener('blur', function(e) {
            // Re-apply formatting on blur
            this.value = formatRupiah(this.value, true);
        });
    });

    // Untuk elemen dengan class .satuan
    document.querySelectorAll('.satuan').forEach(function(el) {
        el.addEventListener('keyup', function(e) {
            this.value = formatRupiah(this.value, false);
        });
        
        el.addEventListener('focus', function(e) {
            // Remove formatting on focus for easier editing
            this.value = this.value.replace(/[^,\d]/g, '');
        });
        
        el.addEventListener('blur', function(e) {
            // Re-apply formatting on blur
            this.value = formatRupiah(this.value, false);
        });
    });
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    initRupiahInputs();
});

// Export functions for module use (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        formatRupiahIntl,
        formatRupiah,
        parseRupiah,
        initRupiahInputs
    };
}
