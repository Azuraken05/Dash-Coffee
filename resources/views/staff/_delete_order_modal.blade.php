<div class="hist-modal" id="deleteModal" hidden>
  <div class="hist-modal-card" role="dialog" aria-modal="true" aria-labelledby="deleteTitle">
    <h3 id="deleteTitle">Delete order <span id="deleteOrderLabel"></span></h3>
    <p class="hist-modal-text">
      The order will be marked <strong>Deleted</strong> in the owner's Sales Report.
      Its ingredients will be returned to stock.
    </p>

    <form method="POST" id="deleteForm" action="{{ url('/staff/orders') }}/{{ old('delete_order') }}">
      @csrf
      @method('DELETE')
      <input type="hidden" name="delete_order" id="deleteOrderId" value="{{ old('delete_order') }}" />

      <label for="deleteReason">Reason for deleting <span class="req">*</span></label>
      <textarea id="deleteReason" name="reason" rows="3" maxlength="255" required
        placeholder="e.g., Duplicate order / customer cancelled">{{ old('reason') }}</textarea>

      @if ($errors->has('reason'))
        <p class="field-error">{{ $errors->first('reason') }}</p>
      @endif

      <div class="hist-modal-actions">
        <button type="button" class="btn btn-soft" data-close-delete>Cancel</button>
        <button type="submit" class="btn btn-danger">Delete order</button>
      </div>
    </form>
  </div>
</div>

<script>
  (function () {
    var modal = document.getElementById('deleteModal');
    var form = document.getElementById('deleteForm');
    var label = document.getElementById('deleteOrderLabel');
    var idInput = document.getElementById('deleteOrderId');
    var reason = document.getElementById('deleteReason');
    if (!modal || !form) return;

    function openModal(button) {
      form.action = button.getAttribute('data-delete-action');
      idInput.value = button.getAttribute('data-delete-order');
      label.textContent = '#' + button.getAttribute('data-delete-order');
      reason.value = '';
      modal.hidden = false;
      reason.focus();
    }

    function closeModal() {
      modal.hidden = true;
    }

    document.addEventListener('click', function (e) {
      var openBtn = e.target.closest('[data-delete-order]');
      if (openBtn) {
        e.preventDefault();
        openModal(openBtn);
        return;
      }
      if (e.target.closest('[data-close-delete]') || e.target === modal) closeModal();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !modal.hidden) closeModal();
    });

    var oldId = @json(old('delete_order'));
    if (oldId) {
      form.action = @json(url('/staff/orders')) + '/' + oldId;
      idInput.value = oldId;
      label.textContent = '#' + oldId;
      modal.hidden = false;
    }
  })();
</script>
