let selectedSlotId = null;
const modal = document.getElementById("confirmModal");

function openConfirmModal(slotId) {
  selectedSlotId = slotId;
  modal.classList.add("show");
}

function closeConfirmModal() {
  modal.classList.remove("show");
  selectedSlotId = null;
}

document.getElementById("confirmBtn").addEventListener("click", function() {
  if (selectedSlotId) {
    window.location.href = "book.php?slot_id=" + selectedSlotId;
  }
});

