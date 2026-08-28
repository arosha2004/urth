<?php
require_once '../config.php';

$result = $conn->query("SELECT * FROM projects ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Urth - Admin Panel</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #eee; }
        .btn { padding: 8px 12px; background: #333; color: #fff; text-decoration: none; border-radius: 3px; border: none; cursor: pointer; }
        .btn-edit { background: #007bff; }
        .btn-delete { background: #dc3545; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        img.thumb { max-width: 100px; height: auto; display: block; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Manage Projects</h2>
            <a href="add_project.php" class="btn">Add New Project</a>
        </div>
        <p><a href="../index.html">Back to Main Site</a></p>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Link URL</th>
                    <th>Images</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['category']) ?></td>
                    <td><a href="../<?= htmlspecialchars($row['link_url']) ?>" target="_blank"><?= htmlspecialchars($row['link_url']) ?></a></td>
                    <td>
                        <?php if($row['image1']): ?><img src="../<?= htmlspecialchars($row['image1']) ?>" class="thumb" alt="img1"><?php endif; ?>
                        <?php if($row['image2']): ?><img src="../<?= htmlspecialchars($row['image2']) ?>" class="thumb" alt="img2"><?php endif; ?>
                        <?php if($row['image3']): ?><img src="../<?= htmlspecialchars($row['image3']) ?>" class="thumb" alt="img3"><?php endif; ?>
                    </td>
                    <td>
                        <a href="edit_project.php?id=<?= $row['id'] ?>" class="btn btn-edit">Edit</a>
                        <form action="delete_project.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this project?');">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if($result->num_rows == 0): ?>
                <tr>
                    <td colspan="5" style="text-align:center;">No projects found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
