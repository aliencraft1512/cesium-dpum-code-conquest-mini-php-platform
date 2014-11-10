<?php
/** This file only contains API's
 *  Contais APIS by this order:
 *  /podim/<problemId> GET
 *  /problem        GET all problems
 *  /problem        POST <Admin> add problem
 *  /problem/<id>   GET problem id
 *  /problem/<id>   PUT <Admin> edit problem
 *  /problem/<id>/podium GET actual result
 *  /problem/<id>/pdf GET pdf
 *  /problem/<id>/submission POST add new submission
 * 
 */


require_once "../vendor/autoload.php";

$app = new \Slim\Slim();
$pdo = new PDO(
    'mysql:host=localhost;dbname=cesium_code',
    'oliveiras',
    'waterFall'
);

$app->get('/problem', function () use ($app, $pdo){
    $a = [['id' => '10','name' => 'Ola10'],['id' => '11','name' => 'Ola12']];
    echo json_encode($a);
});

$app->get('/problem/:id', function () use ($app, $pdo){
    echo '{"id": "10","name": "test"}'; 
});

$app->get('/problem/:id/podium', function () use ($app, $pdo){
    echo "fuckyout";
});


//XDEBUG_SESSION_START=ECLIPSE_DBGP&amp;KEY=14131304091982
$app->get('/podium', function () use ($app, $pdo){
    $sql = "SELECT studentName as name, studentCode as number, points as score from code order by points desc";
    $statement = $pdo->prepare($sql);
    $statement->execute();
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);
    //some magic to convert $r to json (....)
    echo json_encode($results);
});

$app->post('/new', function () use ($app, $pdo){
    //$jsonObj = json_decode($app->request()->getBody(), true);
    $jsonObj = $_POST; 
    
    $studentCode = isset($jsonObj['studentCode']) ? $jsonObj['studentCode'] :  'na' ;
    $studentName = isset($jsonObj['studentName']) ? $jsonObj['studentName'] :  'na' ;
    $problem = isset($jsonObj['problem']) ? $jsonObj['problem'] :  'na' ;
    $email = isset($jsonObj['email']) ? $jsonObj['email'] :  'na' ;
    $path = fileSave();
    $pontos = calcPoints($path);
    
    $sql = "INSERT INTO code (dateCreate,points,studentCode,studentName,problem,email,path) VALUES (NOW(),:points,:studentCode,:studentName,:problem,:email,:path)";
    $pdo->prepare($sql)->execute(
        array(':points' => $pontos,
              ':studentCode' => $studentCode,
              ':studentName' => $studentName,
              ':problem' => $problem,
              ':email' => $email,
              ':path' => $path));
    
    $app->response->redirect('../index.html', 303);
});

function fileSave() {
    $uploads_dir = 'uploads';
    $path = $uploads_dir.'/'.uniqid();
    //CenasX
    if ($_FILES["code"]["error"] <= 0) {
        move_uploaded_file($_FILES["code"]['tmp_name'],$path.".code" );
    }

    if ($_FILES["output"]["error"] <= 0) {
        move_uploaded_file($_FILES["output"]['tmp_name'],$path.".out" );
    }
    
    if ($_FILES["pdf"]["error"] <= 0) {
        move_uploaded_file($_FILES["pdf"]['tmp_name'],$path.".pdf" );
    }
    
    return $path;
}

function calcPoints($filePrefix) 
{
    $cmd = '/usr/bin/perl '.__DIR__.'/verify.pl '.__DIR__.'/t20.in '.__DIR__.'/'.$filePrefix.'.out 2>&1';
    set_time_limit(0);
    $result = shell_exec($cmd);
    //want to save in better place?  no.... yess.. mkdir and move_uploaded_file.
    //script .... $_FILES["file"]["tmp_name"]
    return (int) $result;
}


$app->run();