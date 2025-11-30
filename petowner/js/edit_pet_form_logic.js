function toggleOtherSpeciesEdit(select) {
    const container = select.closest('.modal-body');
    const otherInput = container.querySelector('.otherSpeciesInput');
    const inputField = otherInput.querySelector('input');

    if (select.value === "Other") {
        otherInput.style.display = "block";
        inputField.required = true;
    } else {
        otherInput.style.display = "none";
        inputField.required = false;
        inputField.value = "";
    }
}

function toggleOtherBreedEdit(select) {
    const container = select.closest('.modal-body');
    const otherInput = container.querySelector('.otherBreedInput');
    const inputField = otherInput.querySelector('input');

    if (select.value === "Other") {
        otherInput.style.display = "block";
        inputField.required = true;
    } else {
        otherInput.style.display = "none";
        inputField.required = false;
        inputField.value = "";
    }
}

function toggleDeathDate(select) {
    const container = select.closest('.modal-body');
    const ddate = container.querySelector('.deceased-date');

    if (select.value === "deceased") {
        ddate.style.display = "block";
    } else {
        ddate.style.display = "none";
    }
}