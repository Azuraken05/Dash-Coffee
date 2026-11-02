document.querySelectorAll('[data-tabs]').forEach(function (group) {
    group.addEventListener('click', function (e) {
        var tab = e.target.closest('.tab');
        if (!tab) return;
        group.querySelectorAll('.tab').forEach(function (t) { t.classList.remove('active'); });
        tab.classList.add('active');
    });
});

var pay = document.getElementById('payMethod');
var ref = document.getElementById('payRef');
if (pay && ref) {
    pay.addEventListener('change', function () {
        var cash = pay.value === 'Cash';
        ref.disabled = cash;
        if (cash) ref.value = '';
    });
}

(function () {
  var d = document.getElementById('clkDate'), t = document.getElementById('clkTime');
  if (!d || !t) return;
  function tick() {
    var now = new Date();
    d.textContent = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    t.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  }
  tick();
  setInterval(tick, 1000);
})();

// QR Payment Modal
(function () {
    var form = document.getElementById('orderForm');
    var pay = document.getElementById('payMethod');
    var modal = document.getElementById('qrPaymentModal');
    var title = document.getElementById('qrPaymentTitle');
    var totalDisplay = document.getElementById('qrPaymentTotal');
    var receivedBtn = document.getElementById('paymentReceived');
    var closeBtn = document.getElementById('qrClose');
    var cartTotal = document.getElementById('cartTotal');

    if (!form || !pay || !modal || !receivedBtn) return;

    var paymentConfirmed = false;

    form.addEventListener('submit', function (e) {
        if (pay.value === 'Cash' || paymentConfirmed) {
            return;
        }

        e.preventDefault();

        title.textContent = 'Pay using ' + pay.value;
        totalDisplay.textContent = cartTotal ? cartTotal.textContent : '₱0.00';

        modal.hidden = false;
    });

    receivedBtn.addEventListener('click', function () {
        paymentConfirmed = true;
        modal.hidden = true;
        form.requestSubmit();
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            modal.hidden = true;
        });
    }
})();
