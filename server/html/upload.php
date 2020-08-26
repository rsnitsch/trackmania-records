<?php
	if (!isset($_POST['records'])) {
		die("No data");
	}

	//file_put_contents('debug.log', print_r($_POST, true));
	$json = $_POST['records'];
	$records = json_decode($json, true);
	//print_r($records);

	try {
		$pdo = new \PDO("sqlite:database.db");
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$commands = ['CREATE TABLE IF NOT EXISTS records (
						game         TEXT NOT NULL,
						user         TEXT NOT NULL,
						track        TEXT NOT NULL,
						best         INTEGER NOT NULL,
						PRIMARY KEY (game, user, track)
					  )'];

		foreach ($commands as $command) {
			$pdo->exec($command);
		}

		foreach ($records as $record) {
			//print_r($record);

			$user = $record['user'];
			$track = $record['track'];
			$best = intval($record['best']);

			// Delete previous record.
			$st = $pdo->prepare('DELETE FROM records WHERE user = :user AND track = :track');
			$st->bindParam(':user', $user, PDO::PARAM_STR);
			$st->bindParam(':track', $track, PDO::PARAM_STR);
			$st->execute();

			// Add new record.
			$st = $pdo->prepare("INSERT INTO records (game, user, track, best) VALUES ('Trackmania 2020', :user, :track, :best)");
			$st->bindParam(':user', $user, PDO::PARAM_STR);
			$st->bindParam(':track', $track, PDO::PARAM_STR);
			$st->bindParam(':best', $best, PDO::PARAM_INT);
			$st->execute();
		}

		echo "Success!";
	} catch (PDOException $e) {
		echo 'Database error: '.$e->getMessage();
	}
