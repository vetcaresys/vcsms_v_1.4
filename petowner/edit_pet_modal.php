<?php foreach ($pets as $pet): ?>
    <div class="modal fade" id="editPetModal<?= $pet['pet_id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Pet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body row g-3">
                        <input type="hidden" name="pet_id" value="<?= $pet['pet_id']; ?>">

                        <!-- Pet Name -->
                        <div class="col-md-6">
                            <label class="form-label">Pet Name</label>
                            <input type="text" name="pet_name" class="form-control"
                                value="<?= htmlspecialchars($pet['pet_name']); ?>" required>
                        </div>

                        <!-- Species -->
                        <?php
                        $speciesList = ["Dog", "Cat", "Rabbit", "Bird", "Hamster", "Other"];
                        $currentSpecies = $pet['species'];
                        $isOtherSpecies = !in_array($currentSpecies, $speciesList);
                        ?>

                        <div class="col-md-6">
                            <label class="form-label">Species</label>
                            <select name="species" class="form-select speciesSelect" required
                                onchange="toggleOtherSpeciesEdit(this)">
                                <option value="">Select Species</option>

                                <?php foreach ($speciesList as $sp): ?>
                                    <option value="<?= $sp ?>" <?= $currentSpecies == $sp ? 'selected' : '' ?>>
                                        <?= $sp ?>
                                    </option>
                                <?php endforeach; ?>

                                <option value="Other" <?= $isOtherSpecies ? 'selected' : '' ?>>
                                    Other (specify)
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6 otherSpeciesInput" style="display: <?= $isOtherSpecies ? 'block' : 'none' ?>;">
                            <label class="form-label">Specify Species</label>
                            <input type="text" name="other_species" class="form-control"
                                value="<?= $isOtherSpecies ? htmlspecialchars($currentSpecies) : '' ?>"
                                placeholder="Enter species" <?= $isOtherSpecies ? 'required' : '' ?>>
                        </div>

                        <!-- Breed -->
                        <?php
                        $breeds = [
                            "Aspin",
                            "Labrador Retriever",
                            "German Shepherd",
                            "Golden Retriever",
                            "Shih Tzu",
                            "Pomeranian",
                            "Chihuahua",
                            "Siberian Husky",
                            "Pug",
                            "Beagle",
                            "Dachshund",
                            "Rottweiler",
                            "Pitbull",
                            "Bulldog",
                            "Mixed Breed"
                        ];

                        $currentBreed = $pet['breed'];
                        $isOtherBreed = !in_array($currentBreed, $breeds);
                        ?>

                        <div class="col-md-6">
                            <label class="form-label">Breed</label>
                            <select name="breed" class="form-select breedSelect" required
                                onchange="toggleOtherBreedEdit(this)">
                                <option value="">Select Breed</option>

                                <?php foreach ($breeds as $b): ?>
                                    <option value="<?= $b ?>" <?= $currentBreed == $b ? 'selected' : '' ?>>
                                        <?= $b ?>
                                    </option>
                                <?php endforeach; ?>

                                <option value="Other" <?= $isOtherBreed ? 'selected' : '' ?>>
                                    Other (specify)
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6 otherBreedInput" style="display: <?= $isOtherBreed ? 'block' : 'none' ?>;">
                            <label class="form-label">Specify Breed</label>
                            <input type="text" name="other_breed" class="form-control"
                                value="<?= $isOtherBreed ? htmlspecialchars($currentBreed) : '' ?>"
                                placeholder="Enter breed" <?= $isOtherBreed ? 'required' : '' ?>>
                        </div>

                        <!-- Birth Date -->
                        <div class="col-md-6">
                            <label class="form-label">Birth Date</label>
                            <input type="date" name="birth_date" class="form-control"
                                value="<?= htmlspecialchars($pet['birth_date']); ?>" required>
                        </div>

                        <!-- Upload Photo -->
                        <div class="col-md-6">
                            <label class="form-label">Upload New Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                            <br>
                            <?php if ($pet['photo']): ?>
                                <small>Current:
                                    <img src="../uploads/pets/<?= $pet['photo']; ?>" width="50">
                                </small>
                            <?php endif; ?>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required onchange="toggleDeathDate(this)">
                                <option value="alive" <?= $pet['status'] == 'alive' ? 'selected' : '' ?>>Alive</option>
                                <option value="deceased" <?= $pet['status'] == 'deceased' ? 'selected' : '' ?>>Deceased
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6 deceased-date"
                            style="display: <?= $pet['status'] == 'deceased' ? 'block' : 'none' ?>;">
                            <label class="form-label">Date of Death</label>
                            <input type="date" name="date_of_death" class="form-control"
                                value="<?= htmlspecialchars($pet['date_of_death']); ?>">
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" required>
                                                    <?= htmlspecialchars($pet['description']); ?>
                                                </textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_pet" class="btn btn-success">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>