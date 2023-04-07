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

	function tableForTrackSet($pdo, $trackSet, $selectedUser) {
			$count = 25;

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
					for ($trackNumber = 1; $trackNumber <= 25; $trackNumber++) {
						// Determine best time for track.
						$st = $pdo->prepare("SELECT user, best FROM records WHERE trackSet = :trackSet AND trackNumber = :trackNumber ORDER BY best ASC");
						$st->bindParam(':trackSet', $trackSet, PDO::PARAM_STR);
						$st->bindParam(':trackNumber', $trackNumber, PDO::PARAM_INT);
						$st->execute();
						$bestTimes = $st->fetchAll();
						//print_r($bestTimes);
						$bestTime = $bestTimes[0]['best'];

						// Get users that have driven this time.
						$st = $pdo->prepare("SELECT user FROM records WHERE trackSet = :trackSet AND trackNumber = :trackNumber AND best = :best ORDER BY LOWER(user)");
						$st->bindParam(':trackSet', $trackSet, PDO::PARAM_STR);
						$st->bindParam(':trackNumber', $trackNumber, PDO::PARAM_INT);
						$st->bindParam(':best', $bestTime, PDO::PARAM_INT);
						$st->execute();
						$users = $st->fetchAll(PDO::FETCH_COLUMN, 0);
						//print_r($users);
						
						if ($trackSet != "Training") {
							if ($trackNumber <= 5) {
								$tableColorClass = " class='whiteTracks'";
							} else if ($trackNumber <= 10) {
								$tableColorClass = " class='greenTracks'";
							} else if ($trackNumber <= 15) {
								$tableColorClass = " class='blueTracks'";
							} else if ($trackNumber <= 20) {
								$tableColorClass = " class='redTracks'";
							} else if ($trackNumber <= 25) {
								$tableColorClass = " class='blackTracks'";
							}
						} else {
							$tableColorClass = "";
						}
?>			<tr<?php echo $tableColorClass; ?>>
				<td><?php echo $trackSet." - ".$trackNumber; ?></td>
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

					$st = $pdo->prepare("SELECT user, SUM(best) AS total_time, COUNT(trackNumber) AS count FROM records WHERE trackSet = :track_set GROUP BY user HAVING count = 25 ORDER BY count DESC, total_time ASC");
					$st->bindValue('track_set', $trackSet, PDO::PARAM_STR);
					$st->execute();
					while ($row = $st->fetch()) {
						//print_r($row);
?>			<tr>
				<td><?php echo $row['user']; ?></td>
				<td><?php echo htmlspecialchars($row['total_time'] / 1000.0); ?>s</td>
			</tr>
<?php
					}

					$st = $pdo->prepare("SELECT user, SUM(best) AS total_time, COUNT(trackNumber) AS count FROM records WHERE trackSet = :track_set GROUP BY user HAVING count < 25 ORDER BY count DESC, total_time ASC");
					$st->bindValue('track_set', $trackSet, PDO::PARAM_STR);
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
	<title>trackmania-records</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="bootstrap.min.css">
	<link rel="stylesheet" href="style.css">
	<meta name="referrer" content="same-origin">
</head>
<body>
	<div class="container">
		<h1>trackmania-records</h1>

<?php
	if (file_exists("database.db")) {
		try {
			$pdo = new \PDO("sqlite:database.db");
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			// Display all track sets for which at least one user has driven *all* tracks.
			$st = $pdo->prepare("SELECT DISTINCT trackSet, COUNT(trackNumber) AS count FROM records GROUP BY trackSet, user HAVING count = 25");
			$st->execute();
			$rows = $st->fetchAll();
			foreach ($rows as $row) {
				tableForTrackSet($pdo, $row['trackSet'], $selectedUser);
			}
		} catch (PDOException $e) {
			echo 'Database error: '.htmlspecialchars($e->getMessage());
		}
	} else {
		echo "<p>No records have been uploaded yet...</p>";
	}
?>

		<h2>Upload instructions</h2>

		<p>To upload your Trackmania 2020 records to this page, follow these instructions:</p>

		<ul>
			<li>Download Python 3 from python.org and install it: <a href="https://www.python.org/downloads/">https://www.python.org/downloads/</a></li>
			<li>Open a shell/terminal window (cmd.exe or PowerShell)</li>
			<li>Install the upload tool by executing this command:<br>
				<span class="text-monospace">pip3 install --upgrade upload-tm-records</span></li>
			<li>Now you can always run the following command to upload your latest records to this server:<br>
				<span class="text-monospace">upload-tm-records.exe <?php echo htmlspecialchars($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST']."/upload.php"); ?></span></li>
		</ul>

		<hr>

		<p class="small">trackmania-records is opensource: <a href="https://github.com/rsnitsch/trackmania-records">github.com/rsnitsch/trackmania-records</a></p>
	</div>
</body>
</html>