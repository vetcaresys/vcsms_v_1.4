function toggleOtherBreed() {
    const breedSelect = document.getElementById("breedSelect");
    const otherInput = document.getElementById("otherBreedInput");

    if (breedSelect.value === "Other") {
        otherInput.style.display = "block";
        otherInput.querySelector("input").required = true;
    } else {
        otherInput.style.display = "none";
        otherInput.querySelector("input").required = false;
        otherInput.querySelector("input").value = "";
    }
}