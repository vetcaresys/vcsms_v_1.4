<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<form action="save_pet_owner.php" method="POST">
    <label>Full Name:</label>
    <input type="text" name="full_name" required>

    <label>Contact No:</label>
    <input type="text" name="contact_no">

    <label>Address:</label>
    <textarea name="address"></textarea>

    <label>Email:</label>
    <input type="email" name="email">

    <button type="submit">Add Owner</button>
</form>

</body>
</html>