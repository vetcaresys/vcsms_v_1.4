<!-- add pet modal -->
<div class="modal fade" id="addPetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Pet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body row g-3">

                    <!-- Pet Name -->
                    <div class="col-md-6">
                        <label class="form-label">Pet Name</label>
                        <input type="text" name="pet_name" class="form-control" required>
                    </div>

                    <!-- Species -->
                    <div class="col-md-6">
                        <label class="form-label">Species</label>
                        <select name="species" class="form-select" id="speciesSelect" required onchange="toggleOtherSpecies()">
                            <option value="">Select Species</option>
                            <option value="Dog">Dog</option>
                            <option value="Cat">Cat</option>
                            <option value="Bird">Bird</option>
                            <option value="Rabbit">Rabbit</option>
                            <option value="Reptile">Reptile</option>
                            <option value="Other">Other (specify)</option>
                        </select>
                    </div>

                    <!-- Other Species Input -->
                    <div class="col-md-6" id="otherSpeciesInput" style="display: none;">
                        <label class="form-label">Specify Species</label>
                        <input type="text" name="other_species" class="form-control" placeholder="Enter species">
                    </div>

                    <!-- Breed -->
                    <div class="col-md-6">
                        <label class="form-label">Breed</label>
                        <select name="breed" class="form-select" id="breedSelect" required onchange="toggleOtherBreed()">
                            <option value="">Select Breed</option>

                            <!-- Dog Breeds -->
                            <option value="Aspin">Aspin (Asong Pinoy)</option>
                            <option value="Labrador Retriever">Labrador Retriever</option>
                            <option value="German Shepherd">German Shepherd</option>
                            <option value="Golden Retriever">Golden Retriever</option>
                            <option value="Shih Tzu">Shih Tzu</option>
                            <option value="Pomeranian">Pomeranian</option>
                            <option value="Chihuahua">Chihuahua</option>
                            <option value="Siberian Husky">Siberian Husky</option>
                            <option value="Pug">Pug</option>
                            <option value="Beagle">Beagle</option>
                            <option value="Dachshund">Dachshund</option>
                            <option value="Rottweiler">Rottweiler</option>
                            <option value="Pitbull">Pitbull</option>
                            <option value="Bulldog">Bulldog</option>

                            <option value="Mixed Breed">Mixed Breed</option>
                            <option value="Other">Other (specify)</option>
                        </select>
                    </div>

                    <!-- Other Breed Input -->
                    <div class="col-md-6" id="otherBreedInput" style="display: none;">
                        <label class="form-label">Specify Breed</label>
                        <input type="text" name="other_breed" class="form-control" placeholder="Enter breed">
                    </div>

                    <!-- Birth Date -->
                    <div class="col-md-6">
                        <label class="form-label">Birth Date</label>
                        <input type="date" name="birth_date" class="form-control" required>
                    </div>

                    <!-- Photo -->
                    <div class="col-md-6">
                        <label class="form-label">Upload Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="add_pet" class="btn btn-success">
                        <i class="bi bi-save"></i> Save Pet
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>