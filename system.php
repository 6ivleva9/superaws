<?php

header('Content-Type: application/json;charset=utf-8');

if(isset($_POST['action']))
{
	switch($_POST['action'])
	{
		case 'get_all_data':
			get_all_data();
			break;
		case 'add_new_user':
			add_new_user();
			break;
		
	}
}

//	функция подключения к базе, возвращает объект подключения
function db_connect()
{
	$host = 'superaws.chmsmkrxxgpz.eu-west-1.rds.amazonaws.com';
	$db   = 'superaws';
	$user = 'root';
	$pass = 'rootpass';
	//$charset = 'UTF-8';
	$charset = 'utf8';

	$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
	$opt = [
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES   => false,
			];
	return new PDO($dsn, $user, $pass, $opt);
}

//	функция получения данных из базы
function get_all_data()
{
	//	подключение к базе
	$db=db_connect();

	//	массив, куда складываются полученные данные из базы
	$arr=[];

	$stmt = $db->prepare("	SELECT u.id_user, u.last_name, u.first_name, u.mid_name, 
								IFNULL(p.`photo`, 'no.jpeg') `photo`, 
								IFNULL(k.`know`, '') `know`, 
								IFNULL(s.`skill`, '') `skill`, 
								IFNULL(h.`hobby`, '') `hobby` 
							FROM superaws.users u 
							
							LEFT JOIN superaws.photos p 
							ON u.id_user=p.id_user
							
							LEFT JOIN superaws.knows k 
							ON u.id_user=k.id_user
							
							LEFT JOIN superaws.skills s 
							ON u.id_user=s.id_user
							
							LEFT JOIN superaws.hobbies h 
							ON u.id_user=h.id_user 
							ORDER BY u.id_user;");
	$stmt->execute();
	foreach ($stmt as $row)
	{
		$arr['id_user'][]=$row['id_user'];
		$arr['last_name'][]=$row['last_name'];
		$arr['first_name'][]=$row['first_name'];
		$arr['mid_name'][]=$row['mid_name'];
		$arr['photo'][]=$row['photo'];
		$arr['know'][]=$row['know'];
		$arr['skill'][]=$row['skill'];
		$arr['hobby'][]=$row['hobby'];
	}

	echo json_encode($arr, JSON_UNESCAPED_UNICODE);
	$arr=[];
	$db=null;
}

function add_new_user()
{
	//	подключение к базе
	$db=db_connect();
	$id_user=0;

	try{
		//	начало транзакции
		$db->beginTransaction();

		//	добавление новой записи в таблицу людей
		$sql = "INSERT INTO users (last_name, first_name, mid_name) VALUES (?, ?, ?);";
		$query = $db->prepare($sql);
		$query->execute( array( $_POST['last_name'], $_POST['first_name'], $_POST['mid_name'] ) );
		//	получение уникального номера добавленной записи
		$id_user = $db->lastInsertId();

		//	добавление новой записи о знаниях
		$sql = "INSERT INTO knows (id_user, `know`) VALUES (?,?);";   
		$query = $db->prepare($sql);
		$query->execute( array( $id_user, $_POST['know'] ) );

		//	добавление новой записи о навыках
		$sql = "INSERT INTO skills (id_user, `skill`) VALUES (?,?);";   
		$query = $db->prepare($sql);
		$query->execute( array( $id_user, $_POST['skill'] ) );

		//	добавление новой записи об увлечениях
		$sql = "INSERT INTO hobbies (id_user, `hobby`) VALUES (?,?);";   
		$query = $db->prepare($sql);
		$query->execute( array( $id_user, $_POST['hobby'] ) );

		if($_POST['photo']!="")
		{
			$data=$_POST['photo'];

			if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
				$data = substr($data, strpos($data, ',') + 1);
				$type = strtolower($type[1]);
			
				if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png', 'bmp'])) {
					throw new \Exception('invalid image type');
				}
			
				$data = base64_decode($data);
			
				if ($data === false) {
					$db->rollBack();
					throw new \Exception('base64_decode failed');
					echo json_encode('base64_decode failed', JSON_UNESCAPED_UNICODE);
					die();
				}
			} else {
				$db->rollBack();
				throw new \Exception('did not match data URI with image data');
				echo json_encode('did not match data URI with image data', JSON_UNESCAPED_UNICODE);
				die();
			}
			
			//	формирование имени файла для фото на основе генератора чисел
			$f_name=rand().'.'.$type;
			//	сохранение файла
			file_put_contents('s3/img/'.$f_name, $data);

			//	добавление новой записи о фото
			$sql = "INSERT INTO photos (id_user, `photo`) VALUES (?,?);";   
			$query = $db->prepare($sql);
			$query->execute([$id_user, $f_name]);
		}

		//	если все прошло по плану, то зафиксировать измененияв базе
		$db->commit();
	}
	//	если во время выполнения транзакци чтото пошло не так, то нужно откатить изменения
	catch(Exception $e){
		// echo $e->getMessage();
		echo json_encode(false, JSON_UNESCAPED_UNICODE);
		$db->rollBack();
		die();
	}

	echo json_encode(true, JSON_UNESCAPED_UNICODE);
	$db=null;
}

?>
