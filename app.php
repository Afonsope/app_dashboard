<?php

class Dashboard {
    public $data_inicio;
    public $data_fim;
    public $numeroVendas;
    public $totalVendas;
    public $clientesAtivos;
    public $clientesInativos;
    public $totalReclamacoes;
    public $totalElogios;
    public $totalSugestoes;
    public $totalDespesas;

    public function __get($atributo) {
        return $this->$atributo;
    }

    public function __set($atributo, $valor) {
        $this->$atributo = $valor;
        return $this;
    }
}

class Conexao {
    private $host = 'localhost';
    private $dbname = 'dashboard';
    private $user = 'root';
    private $pass = '';

    public function conectar() {
        try {
            $conexao = new PDO(
                "mysql:host=$this->host;dbname=$this->dbname",
                $this->user,
                $this->pass
            );

            $conexao->exec('set charset utf8');
            return $conexao;

        } catch (PDOException $e) {
            echo '<p>'.$e->getMessage().'</p>';
        }
    }
}

class Bd {
    private $conexao;
    private $dashboard;

    public function __construct(Conexao $conexao, Dashboard $dashboard) {
        $this->conexao = $conexao->conectar();
        $this->dashboard = $dashboard;
    }

    // VENDAS
    public function getNumeroVendas() {
        $query = "
            SELECT COUNT(*) AS numero_vendas
            FROM tb_vendas
            WHERE data_venda BETWEEN :data_inicio AND :data_fim
        ";

        $stmt = $this->conexao->prepare($query);
        $stmt->bindValue(':data_inicio', $this->dashboard->__get('data_inicio'));
        $stmt->bindValue(':data_fim', $this->dashboard->__get('data_fim'));
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_OBJ)->numero_vendas;
    }

    public function getTotalVendas() {
        $query = "
            SELECT IFNULL(SUM(total),0) AS total_vendas
            FROM tb_vendas
            WHERE data_venda BETWEEN :data_inicio AND :data_fim
        ";

        $stmt = $this->conexao->prepare($query);
        $stmt->bindValue(':data_inicio', $this->dashboard->__get('data_inicio'));
        $stmt->bindValue(':data_fim', $this->dashboard->__get('data_fim'));
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_OBJ)->total_vendas;
    }

    // CLIENTES
    public function getClientesAtivos() {
        $query = "SELECT COUNT(*) AS ativos FROM tb_clientes WHERE cliente_ativo = 1";
        return $this->conexao->query($query)->fetch(PDO::FETCH_OBJ)->ativos;
    }

    public function getClientesInativos() {
        $query = "SELECT COUNT(*) AS inativos FROM tb_clientes WHERE cliente_ativo = 0";
        return $this->conexao->query($query)->fetch(PDO::FETCH_OBJ)->inativos;
    }

    // CONTATOS
    public function getReclamacoes() {
        // tipo_contato = 1
        $query = "SELECT COUNT(*) AS total FROM tb_contatos WHERE tipo_contato = 1";
        return $this->conexao->query($query)->fetch(PDO::FETCH_OBJ)->total;
    }

    public function getSugestoes() {
        // tipo_contato = 2
        $query = "SELECT COUNT(*) AS total FROM tb_contatos WHERE tipo_contato = 2";
        return $this->conexao->query($query)->fetch(PDO::FETCH_OBJ)->total;
    }

    public function getElogios() {
        // tipo_contato = 3
        $query = "SELECT COUNT(*) AS total FROM tb_contatos WHERE tipo_contato = 3";
        return $this->conexao->query($query)->fetch(PDO::FETCH_OBJ)->total;
    }

    // DESPESAS
    public function getTotalDespesas() {
        $query = "
            SELECT IFNULL(SUM(total),0) AS total_despesas
            FROM tb_despesas
            WHERE data_despesa BETWEEN :data_inicio AND :data_fim
        ";

        $stmt = $this->conexao->prepare($query);
        $stmt->bindValue(':data_inicio', $this->dashboard->__get('data_inicio'));
        $stmt->bindValue(':data_fim', $this->dashboard->__get('data_fim'));
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_OBJ)->total_despesas;
    }
}

// ==========================
// INSTÂNCIAS E DATAS
// ==========================
$dashboard = new Dashboard();
$conexao = new Conexao();

$competencia = explode('-', $_GET['competencia']);
$ano = $competencia[0];
$mes = $competencia[1];

$dias_do_mes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);

$dashboard->__set('data_inicio', "$ano-$mes-01");
$dashboard->__set('data_fim', "$ano-$mes-$dias_do_mes");

$db = new Bd($conexao, $dashboard);

// ==========================
// SET DOS DADOS
// ==========================
$dashboard->__set('numeroVendas', $db->getNumeroVendas());
$dashboard->__set('totalVendas', $db->getTotalVendas());
$dashboard->__set('clientesAtivos', $db->getClientesAtivos());
$dashboard->__set('clientesInativos', $db->getClientesInativos());
$dashboard->__set('totalReclamacoes', $db->getReclamacoes());
$dashboard->__set('totalSugestoes', $db->getSugestoes());
$dashboard->__set('totalElogios', $db->getElogios());
$dashboard->__set('totalDespesas', $db->getTotalDespesas());

echo json_encode($dashboard);

?>