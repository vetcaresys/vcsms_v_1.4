function toggleOtherBreedEdit(select) {
    const modalBody = select.closest('.modal-body');
    const otherContainer = modalBody.querySelector('.otherBreedInput');
    const input = otherContainer.querySelector('input');

    if (select.value === "Other") {
        otherContainer.style.display = "block";
        input.required = true;
    } else {
        otherContainer.style.display = "none";
        input.required = false;
        input.value = ""; // clears previously typed text kay nipili na siya ug existing breed
    }
}