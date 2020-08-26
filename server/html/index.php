<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Trackmania Records</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="bootstrap.min.css">
	<meta name="referrer" content="same-origin">
</head>
<body>
	<div class="container">
		<h1>Trackmania Records</h1>

		<table class="table table-striped table-hover table-sm">
			<tr>
				<th scope="col">Track</th>
				<th scope="col">Best time</th>
				<th scope="col">Driven by</th>
			</tr>
			<?php
				try {
					$pdo = new \PDO("sqlite:database.db");
					$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

					for ($i = 1; $i <= 25; $i++) {
						$track = sprintf("Training - %02d", $i);

						// Determine best time for track.
						$st = $pdo->prepare("SELECT best FROM records WHERE track = :track ORDER BY best ASC LIMIT 1");
						$st->bindParam(':track', $track, PDO::PARAM_STR);
						$st->execute();
						$row = $st->fetch();
						$best = $row['best'];

						// Get users that have driven this time.
						$st = $pdo->prepare("SELECT user FROM records WHERE track = :track AND best = :best ORDER BY user");
						$st->bindParam(':track', $track, PDO::PARAM_STR);
						$st->bindParam(':best', $best, PDO::PARAM_INT);
						$st->execute();
						$users = $st->fetchAll(PDO::FETCH_COLUMN, 0);
						//print_r($users);
?>			<tr>
				<td><?php echo $track; ?></td>
				<td><?php echo htmlspecialchars($best / 1000.0); ?>s</td>
				<td><?php echo htmlspecialchars(implode(',', $users)); ?></td>
			</tr>
<?php
					}
				} catch (PDOException $e) {
					echo 'Database error: '.htmlspecialchars($e->getMessage());
				}
			?>
		</table>

		<h2>Total time per user</h2>

		<table class="table table-striped table-hover table-sm">
			<tr>
				<th scope="col">User</th>
				<th scope="col">Total time</th>
			</tr>
			<?php
				try {
					$pdo = new \PDO("sqlite:database.db");
					$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

					$results = $pdo->query("SELECT user, SUM(best) AS total_time, COUNT(track) AS count FROM records GROUP BY user ORDER BY count DESC, total_time ASC");
					while ($row = $results->fetch()) {
						//print_r($row);
?>			<tr>
				<td><?php echo $row['user']; ?></td>
				<td><?php if ($row['count'] == 25) { echo htmlspecialchars($row['total_time'] / 1000.0); } else { echo "&#8734;"; } ?>s</td>
			</tr>
<?php
					}
				} catch (PDOException $e) {
					echo 'Database error: '.htmlspecialchars($e->getMessage());
				}
			?>
		</table>

		<h2>Upload instructions</h2>

		<ul>
			<li>Download Python 3 from python.org and install it: <a href="https://www.python.org/downloads/">https://www.python.org/downloads/</a></li>
			<li>Open a shell/terminal window (cmd.exe or PowerShell)</li>
			<li>Install the upload script by executing this command:<br>
				<span class="text-monospace">pip3 install --upgrade upload-tm-records</span></li>
			<li>Now you can always run the following command to upload your latest records to this server:<br>
				<span class="text-monospace">upload-tm-records.exe <?php echo htmlspecialchars($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])."upload.php"; ?></span></li>
		</ul>
	</div>
</body>
</html>