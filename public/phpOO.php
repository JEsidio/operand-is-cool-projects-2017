<?php

class Usuario
{
    protected $id;
	protected $nome;
    protected $email;

    public function setId($id)
    {
        $this->id = $id;
    }

	public function setNome($nome)
    {
        $this->nome = $nome;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getId()
    {
        return $this->id;
    }

	public function getNome()
    {
        return $this->nome;
    }

    public function getEmail()
    {
        return $this->email;
    }
}

//$usuario = new Usuario();
//
//$usuario->setId(1);
//$usuario->setNome('Marvin');
//$usuario->setEmail('marvin@coracaodeouro.com');

//echo $usuario->getId() . '<br>';
//echo $usuario->getNome() . '<br>';
//echo $usuario->getEmail() . '<br>';


class Admin extends Usuario
{
	protected $senha;

	public function setSenha($senha){
		$this->senha = md5($senha);
	}

	public function getSenha(){
		return $this->senha;
	}
}

$admin = new Admin();

$admin->setId(15);
$admin->setNome('Marvin');
$admin->setEmail('marvin@coracaodeouro.com');
$admin->setSenha('123456');

echo $admin->getId() . '<br>';
echo $admin->getNome() . '<br>';
echo $admin->getEmail() . '<br>';
echo $admin->getSenha() . '<br>';


//$usuario = new Usuario();
//var_dump($usuario);