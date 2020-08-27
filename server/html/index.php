<?php
	// TODO: Improve generation of upload.php URL (currently it just uses hostname + "/upload.php")

	header("Content-Security-Policy: default-src 'self'");
	$selectedUser = isset($_GET['user']) ? $_GET['user'] : null;

	// Returns the best time of the specified user along with the rank that this time
	// results in compared to the times by other users.
	function getBestTimeByUser($bestTimes, $user) {
		$rank = 1;
		for ($i = 0; $i < count($bestTimes); $i++) {
			$bestTime = $bestTimes[$i];
			if ($bestTime['user'] == $user) {
				return array($bestTime['best'], $rank);
			}

			if ($i < (count($bestTimes) - 1) && $bestTimes[$i+1]['best'] > $bestTime['best']) {
				$rank++;
			}
		}

		return null;
	}

	function tableForTrackSet($trackSet, $selectedUser) {
		if ($trackSet == "Training")
			$count = 25;
		else if ($trackSet == "Summer 2020") {
			$count = 25;
		} else {
			throw Exception("Unknown track set");
		}

		echo "		<h2>".htmlspecialchars($trackSet)." - Records</h2>";
		?>

		<table class="table table-striped table-hover table-sm">
			<tr>
				<th scope="col">Track</th>
				<th scope="col">Best time</th>
				<th scope="col">Driven by</th>
<?php
					if ($selectedUser) {
						echo "				<th scope=\"col\">".htmlspecialchars($selectedUser)."'s time</th>\n";
						echo "				<th scope=\"col\">Absolute delta</th>\n";
						echo "				<th scope=\"col\">Relative delta (%)</th>\n";
						echo "				<th scope=\"col\">".htmlspecialchars($selectedUser)."'s rank</th>\n";
					}
?>
			</tr>
<?php
				try {
					$pdo = new \PDO("sqlite:database.db");
					$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

					
					for ($i = 1; $i <= 25; $i++) {
						$track = sprintf("$trackSet - %02d", $i);

						// Determine best time for track.
						$st = $pdo->prepare("SELECT user, best FROM records WHERE track = :track ORDER BY best ASC");
						$st->bindParam(':track', $track, PDO::PARAM_STR);
						$st->execute();
						$bestTimes = $st->fetchAll();
						//print_r($bestTimes);
						$bestTime = $bestTimes[0]['best'];

						// Get users that have driven this time.
						$st = $pdo->prepare("SELECT user FROM records WHERE track = :track AND best = :best ORDER BY LOWER(user)");
						$st->bindParam(':track', $track, PDO::PARAM_STR);
						$st->bindParam(':best', $bestTime, PDO::PARAM_INT);
						$st->execute();
						$users = $st->fetchAll(PDO::FETCH_COLUMN, 0);
						//print_r($users);
?>			<tr>
				<td><?php echo $track; ?></td>
				<td><?php echo htmlspecialchars($bestTime / 1000.0); ?>s</td>
				<td><?php echo htmlspecialchars(implode(', ', $users)); ?></td>
<?php
					if ($selectedUser) {
						$bestTimeByUser = getBestTimeByUser($bestTimes, $selectedUser);
						if ($bestTimeByUser) {
							echo "				<td>".($bestTimeByUser[0] / 1000.0)."s</td>\n";
							echo "				<td>".sprintf("%.3f", $bestTimeByUser[0] / 1000.0 - $bestTime / 1000.0)."s</td>\n";
							echo "				<td>".sprintf("%.1f", $bestTimeByUser[0] / $bestTime * 100)."%</td>\n";
							echo "				<td>".$bestTimeByUser[1]."</td>\n";
						} else {
							echo "				<td>-</td>\n";
							echo "				<td>-</td>\n";
							echo "				<td>-</td>\n";
							echo "				<td>-</td>\n";
						}
					}
				?>
			</tr>
<?php
					}
				} catch (PDOException $e) {
					echo 'Database error: '.htmlspecialchars($e->getMessage());
				}
			?>
		</table>

<?php echo "		<h3>".htmlspecialchars($trackSet)." - Total time per user</h3>"; ?>

		<table class="table table-striped table-hover table-sm">
			<tr>
				<th scope="col">User</th>
				<th scope="col">Total time</th>
			</tr>
<?php
				try {
					$pdo = new \PDO("sqlite:database.db");
					$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

					$st = $pdo->prepare("SELECT user, SUM(best) AS total_time, COUNT(track) AS count FROM records WHERE track LIKE :track_set GROUP BY user HAVING count = 25 ORDER BY count DESC, total_time ASC");
					$st->bindValue('track_set', addcslashes("$trackSet", "?%")."%", PDO::PARAM_STR);
					$st->execute();
					while ($row = $st->fetch()) {
						//print_r($row);
?>			<tr>
				<td><?php echo $row['user']; ?></td>
				<td><?php echo htmlspecialchars($row['total_time'] / 1000.0); ?>s</td>
			</tr>
<?php
					}

					$st = $pdo->prepare("SELECT user, SUM(best) AS total_time, COUNT(track) AS count FROM records WHERE track LIKE :track_set GROUP BY user HAVING count < 25 ORDER BY count DESC, total_time ASC");
					$st->bindValue('track_set', addcslashes("$trackSet", "?%")."%", PDO::PARAM_STR);
					$st->execute();
					while ($row = $st->fetch()) {
						//print_r($row);
?>			<tr>
				<td><?php echo $row['user']; ?></td>
				<td>&#8734;s</td>
			</tr>
<?php
					}
				} catch (PDOException $e) {
					echo 'Database error: '.htmlspecialchars($e->getMessage());
				}
			?>
		</table>
<?php
	}
?><!doctype html>
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

<?php tableForTrackSet("Training", $selectedUser); ?>
<?php tableForTrackSet("Summer 2020", $selectedUser); ?>

		<h2>Upload instructions</h2>

		<ul>
			<li>Download Python 3 from python.org and install it: <a href="https://www.python.org/downloads/">https://www.python.org/downloads/</a></li>
			<li>Open a shell/terminal window (cmd.exe or PowerShell)</li>
			<li>Install the upload script by executing this command:<br>
				<span class="text-monospace">pip3 install --upgrade upload-tm-records</span></li>
			<li>Now you can always run the following command to upload your latest records to this server:<br>
				<span class="text-monospace">upload-tm-records.exe <?php echo htmlspecialchars($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST']."/upload.php"); ?></span></li>
		</ul>
	</div>
</body>
</html>