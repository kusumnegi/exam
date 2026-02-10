const modal = document.getElementById("confirmModal");

modal.addEventListener("show.bs.modal", e => {
    const btn = e.relatedTarget;
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    const status = btn.dataset.status;

    document.getElementById("modalStudentId").value = id;
    document.getElementById("modalNewStatus").value = status == 1 ? 0 : 1;
    document.getElementById("modalStudentName").innerText = name;
    document.getElementById("modalAction").innerText = status == 1 ? "block" : "unblock";
});