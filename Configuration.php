<?php
require_once "core/Database.php";
require_once "core/FilePresenter.php";
require_once "core/MustachePresenter.php";
require_once "core/Router.php";
require_once "core/EmailSender.php";

require_once "controller/HomeController.php";
require_once "controller/GroupController.php";
require_once "controller/SongController.php";
require_once "controller/TourController.php";
require_once "controller/RegistroController.php";
require_once "controller/LoginController.php";
require_once "controller/PerfilController.php";
require_once "controller/PartidaController.php";
require_once "controller/PreguntaController.php";

require_once "model/GroupModel.php";
require_once "model/SongModel.php";
require_once "model/TourModel.php";
require_once "model/RegistroModel.php";
require_once "model/LoginModel.php";
require_once "model/EmailModel.php";
require_once "model/PerfilModel.php";
require_once "model/CategoriaModel.php";
require_once "model/PreguntaModel.php";
require_once "model/PartidaModel.php";
require_once "model/RolModel.php";


include_once 'vendor/mustache/src/Mustache/Autoloader.php';

class Configuration
{
  public function getDatabase(): Database
  {
    $config = $this->getIniConfig();

    return new Database(
      $config["database"]["server"],
      $config["database"]["user"],
      $config["database"]["dbname"],
      $config["database"]["pass"]
    );
  }

  public function getEmailSender(): EmailSender
  {
    $config = $this->getIniConfig();

    return new EmailSender(
      $config["email"]["host"],
      $config["email"]["username"],
      $config["email"]["password"],
      $config["email"]["port"]
    );
  }

  public function getIniConfig()
  {
    return parse_ini_file("configuration/config.ini", true);
  }

  public function getRegistroController(): RegistroController
  {
    return new RegistroController(
      new RegistroModel($this->getDatabase()),
      $this->getViewer(),
      $this->getEmailSender()
    );
  }

  public function getPerfilController(): PerfilController
  {
    return new PerfilController(
      new PerfilModel($this->getDatabase()),
      $this->getViewer()
    );
  }

  public function getLoginController(): LoginController
  {
    return new LoginController(
      new LoginModel($this->getDatabase()),
      $this->getViewer(),
      new RolModel($this->getDatabase())
    );
  }

  public function getPartidaController(): PartidaController
  {
    return new PartidaController(
      new PartidaModel($this->getDatabase()),
      new CategoriaModel($this->getDatabase()),
      $this->getViewer()
    );
  }

  public function getPreguntaController(): PreguntaController
  {
    return new PreguntaController(
      new PreguntaModel($this->getDatabase()),
      $this->getViewer()
    );
  }

  public function getSongController(): SongController
  {
    return new SongController(
      new SongModel($this->getDatabase()),
      $this->getViewer()
    );
  }

  public function getTourController(): TourController
  {
    return new TourController(
      new TourModel($this->getDatabase()),
      $this->getViewer()
    );
  }

  public function getHomeController(): HomeController
  {
    return new HomeController($this->getViewer());
  }


  public function getGroupController(): GroupController
  {
    return new GroupController(new GroupModel($this->getDatabase()), $this->getViewer());
  }

  public function getRouter(): Router
  {
    return new Router("getHomeController", "show", $this);
  }

  public function getViewer(): MustachePresenter
  {
    return new MustachePresenter("view");
  }
}
