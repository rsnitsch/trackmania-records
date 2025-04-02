<?php
	if (!isset($_POST['records'])) {
		http_response_code(400);
		die("No records");
	}

	if (!isset($_POST['client_name'])) {
		http_response_code(400);
		die("No client_name");
	}

	if (!isset($_POST['client_version'])) {
		http_response_code(400);
		die("No client_version");
	}

	define('REQUIRED_CLIENT_VERSION', '1.0.0');
	if ($_POST['client_version'] !== REQUIRED_CLIENT_VERSION) {
		http_response_code(400);
		die("Your client is outdated. Please use version ".REQUIRED_CLIENT_VERSION);
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
						trackSet	 TEXT NOT NULL,
						trackNumber  INTEGER NOT NULL,
						best         INTEGER NOT NULL,
						PRIMARY KEY (game, user, trackSet, trackNumber)
					  )'];

		foreach ($commands as $command) {
			$pdo->exec($command);
		}

		foreach ($records as $record) {
			//print_r($record);

            if (!isset($record['user'])) {
                http_response_code(400);
                die("No user key in record");
            }
            if (!isset($record['trackSet'])) {
                http_response_code(400);
                die("No trackSet key in record");
            }
            if (!isset($record['best'])) {
                http_response_code(400);
                die("No best key in record");
            }

			$user = $record['user'];
			$trackSet = substr($record['trackSet'], 0, strpos($record['trackSet'], ' - '));
			$trackNumber = intval(substr($record['trackSet'], strpos($record['trackSet'], ' - ') + 3));
			$best = intval($record['best']);

			// Delete previous record.
			$st = $pdo->prepare('DELETE FROM records WHERE user = :user AND trackSet = :trackSet AND trackNumber = :trackNumber');
			$st->bindParam(':user', $user, PDO::PARAM_STR);
			$st->bindParam(':trackSet', $trackSet, PDO::PARAM_STR);
			$st->bindParam(':trackNumber', $trackNumber, PDO::PARAM_INT);
			$st->execute();

			// Add new record.
			$st = $pdo->prepare("INSERT INTO records (game, user, trackSet, trackNumber, best) VALUES ('Trackmania 2020', :user, :trackSet, :trackNumber, :best)");
			$st->bindParam(':user', $user, PDO::PARAM_STR);
			$st->bindParam(':trackSet', $trackSet, PDO::PARAM_STR);
			$st->bindParam(':trackNumber', $trackNumber, PDO::PARAM_INT);
			$st->bindParam(':best', $best, PDO::PARAM_INT);
			$st->execute();
		}

		echo "Success!";
	} catch (PDOException $e) {
		http_response_code(500);
		die('Database error: '.$e->getMessage());
	}
