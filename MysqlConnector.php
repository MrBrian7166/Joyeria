<?php
	class MysqlConnector
	{
		private $server;
		private $connUser;
		private $connPassword;
		private $connDb;
		var $connection;

		function __construct()
		{
			$this->server = "bdatos";
			$this->connUser = "root";
			$this->connPassword = "root";
			$this->connDb = "Joyeria";
		}

		public function Connect(){
			$this->connection = mysqli_connect(
				$this->server, $this->connUser,
				$this->connPassword, $this->connDb
			);

			if (!$this->connection){
				echo "Trying to connect and failed: " . mysqli_connect_error();
			}
		}

		public function ExecuteQuery($query){
			$result = mysqli_query($this->connection, $query);
			if(!$result){
				echo "<br>There is no possibility to execute query: " . mysqli_error($this->connection);
				exit;
			}
			return $result;
		}

		public function CloseConnection(){
			mysqli_close($this->connection);
			$this->connection = null;
		}
	}
?>
