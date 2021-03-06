<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tarefa{

  private $CI, $id, $projetoId, $tarefaId, $usuarioId, $titulo, $descricao, $dataCadastro, $dataEntrega, $estado;

  public function __construct(){
    $this->CI =& get_instance();
    $config = array (
      'smtp_user' => 'seu@email',
      'smtp_pass' => 'suasenha',
      'mailtype'  => 'html', 
      'charset'   => 'utf-8',
      'protocol' => 'smtp',
      'smtp_host' => 'ssl://smtp.googlemail.com',
      'smtp_port' => 465,
     );
    $this->CI->load->library('email', $config);
    $this->CI->email->set_newline("\r\n");
  }

  public function setId($id){
    if(is_null($id) || empty($id) || $id == ''){
      $this->id = 'DEFAULT';
    }else{
      $this->id = $id;
    }
  }
  public function getId(){
    return $this->id;
  }
  
  public function setUsuarioId($usuarioId){
    $this->usuarioId = $usuarioId;
  }
  public function getUsuarioId(){
    return $this->usuarioId;
  }
//   public function setTarefaId($tarefaId){
//     $this->id = $tarefaId;
//   }
//   public function getTarefaId(){
//     return $this->id;
//   }
  public function setProjetoId($projetoId){
    $this->projetoId = $projetoId;
  }
  public function getProjetoId(){
    return $this->projetoId;
  }

  public function setTitulo($titulo){
    $this->titulo = $titulo;
  }
  public function getTitulo(){
    return $this->titulo;
  }
  public function setDescricao($descricao){
    $this->descricao = $descricao;
  }
  public function getDescricao(){
    return $this->descricao;
  }

  public function setDataCadastro($dataCadastro = 'NOW()'){
    $this->dataCadastro = $dataCadastro;
  }
  public function getDataCadastro(){
    return $this->dataCadastro;
  }

  public function setDataEntrega($dataEntrega = 'NOW()'){
    $this->dataEntrega = $dataEntrega;
  }
  public function getDataEntrega(){
    return $this->dataEntrega;
  }

  public function setEstado($estado){
    if(is_null($estado) || empty($estado) || $estado == ''){
      $this->estado = 'DEFAULT';
    }else{
      $this->estado = $estado;
    }
  }
  public function getEstado(){
    return $this->estado;
  }

  // Funções de execução
  public function pesquisarTarefa($tipo){
    if($tipo == 1){
      $sql = "SELECT dt.id, dt.titulo, dt.descricao, dt.dataEntrega, dt.id, dt.estado, dt.projeto_id, dut.tarefa_id 
            FROM data__tarefa dt, data__projeto dp, data__usuario_tarefa dut, data__participante dpa, data__usuario du
            WHERE dp.id = dt.projeto_id
            AND dpa.projeto_id = dp.id
            AND dpa.usuario_id = du.id
            AND dut.tarefa_id = dt.id
            AND dp.id = $this->projetoId
            AND dpa.estado = 1
            ORDER BY tarefa_id
            ";
    
      $sqlSemPart = "SELECT dt.id, dt.titulo, dt.descricao, dt.dataEntrega, dt.id, dt.estado, dt.projeto_id, dut.tarefa_id 
              FROM data__tarefa dt, data__projeto dp, data__usuario_tarefa dut, data__usuario du
              WHERE dp.id = dt.projeto_id
              AND dut.tarefa_id = dt.id
              AND dp.id = $this->projetoId
              ORDER BY tarefa_id
              ";

      $query = $this->CI->db->query($sql);
      if($query->num_rows() > 0){
        $tarefaResult = $query->result();
      }else{
        $query = $this->CI->db->query($sqlSemPart);
        $tarefaResult = $query->result();
      }

      $tarefasArray = array();
      $lastKey = '';
      foreach($query->result() as $tarefas_ambiguas){
        if(empty($lastKey)){
          array_push($tarefasArray, $tarefas_ambiguas);
        }else{
          if($lastKey != $tarefas_ambiguas->tarefa_id){
            array_push($tarefasArray, $tarefas_ambiguas);
          }
        }
        $lastKey = $tarefas_ambiguas->tarefa_id;
      }
      if($query){
        return array(
          'auth' => 1,
          'tarefa' => $tarefasArray,
          'participantes' => $tarefaResult,
        );
      }else{
        return array(
          'auth' => 0,
        );
      }
    }else if($tipo == 2){
      $tarefa = $this->CI->db->query("SELECT * FROM `data__tarefa` WHERE id = $this->id");  
      $tarefaResult = $tarefa->result();
      $projeto = $this->CI->db->query("SELECT * FROM `data__projeto` WHERE id = " . $tarefaResult[0]->projeto_id)->result();  

      $sql = "SELECT DISTINCT(dt.id), dut.tarefa_id, dt.dataEntrega as dEntrega, dt.*, du.* FROM  `data__usuario_tarefa` dut JOIN  `data__tarefa` dt ON dut.tarefa_id = dt.id JOIN  `data__usuario` du ON dut.usuario_id = du.id WHERE dut.tarefa_id = $this->id AND dut.usuario_id <> " . $projeto[0]->usuario_id;

      $result = $this->CI->db->query($sql);
      if($tarefa->num_rows() > 0){
        return $data = array(
          'auth' => 1,
          'tarefa' => $result->result()
        );
      }else{
        return $data = array('auth' => 0);
      }
    }else if($tipo == 3){
      $tarefa = $this->CI->db->query("SELECT * FROM `data__tarefa` WHERE id = $this->id");  
      $tarefaResult = $tarefa->result();
      $projeto = $this->CI->db->query("SELECT * FROM `data__projeto` WHERE id = " . $tarefaResult[0]->projeto_id)->result();  

      $sql = "SELECT DISTINCT(dt.id), dt.estado as t_estado, dt.dataEntrega as dEntrega, dut.tarefa_id, dt.*, du.* FROM  `data__usuario_tarefa` dut JOIN  `data__tarefa` dt ON dut.tarefa_id = dt.id JOIN  `data__usuario` du ON dut.usuario_id = du.id WHERE dut.tarefa_id = $this->id";
      
      $participantes = $this->CI->db->query("SELECT DISTINCT(dpt.usuario_id), du.* FROM  `data__participante` dpt JOIN  `data__usuario` du ON dpt.usuario_id = du.id WHERE dpt.projeto_id = " . $tarefaResult[0]->projeto_id . " AND dpt.estado = 1")->result();
      
      $participantesTarefas = $this->CI->db->query("SELECT DISTINCT(du.id) FROM  `data__usuario_tarefa` dut JOIN  `data__usuario` du ON dut.usuario_id = du.id WHERE dut.tarefa_id = $this->id")->result();
      
      $result = $this->CI->db->query($sql);
      if($tarefa->num_rows() > 0){
        return $data = array(
          'auth' => 1,
          'tarefa' => $result->result(),
          'participantes' => $participantes, 
          'participantesTarefas' => $participantesTarefas 
        );
      }else{
        return $data = array('auth' => 0);
      }
    }
  }

  public function alterarEstado(){
    $query = $this->CI->db->query("UPDATE `data__tarefa` SET estado = $this->estado WHERE id = $this->id;");
    if($this->CI->db->affected_rows() > 0){
      return $data = array('auth' => 1);
    }else{
      return $data = array('auth' => 0);
    }
  }
  public function carregarTarefa($tipo){
    if($tipo == 1){
      $sql = "SELECT * FROM  `data__usuario_tarefa` dut 
      JOIN  `data__tarefa` dt 
      ON dut.tarefa_id = dt.id WHERE usuario_id = $this->usuarioId AND dt.projeto_id = $this->projetoId";
      $result = $this->CI->db->query($sql);
      if($result->num_rows() > 0){
        return $data = array(
          'auth' => 1,
          'tarefas' => $result->result()
        );
      }else{
        return $data = array('auth' => 0);
      }
    }
  }
  public function criarTarefa(){
    $sql = "INSERT INTO `data__tarefa` (`id`, `titulo`, `descricao`, `dataCadastro`, `dataEntrega`, `estado`, `projeto_id`)
    VALUES (DEFAULT,'$this->titulo', '$this->descricao', now(), '$this->dataEntrega', 1, $this->projetoId)";

    $query = $this->CI->db->query($sql);
    $tarefaQueryId = $this->CI->db->insert_id();
    if($query){
      return $data = array(
        'auth' => 1,
        'tarefaId' => $tarefaQueryId
      );
    }else{
      return $data = array('auth' => 0);
    }
  }
  public function enviarNotificacao($html, $tituloEmail){
    $projetoSql = "SELECT * FROM `data__projeto` WHERE id = $this->projetoId";
    $projetoQuery = $this->CI->db->query($projetoSql);
    $projetoResult = $projetoQuery->result();
    
    $tarefaSql = "SELECT * FROM `data__tarefa` WHERE id = $this->id";
    $tarefaQuery = $this->CI->db->query($tarefaSql);
    $tarefaResult = $tarefaQuery->result();
    
    $errorCounter = 0;
    foreach($this->usuarioId as $participanteId){
      $usuarioSql = "SELECT * FROM `data__usuario` WHERE id = $participanteId";
      $usuarioQuery = $this->CI->db->query($usuarioSql);
      $usuarioResult = $usuarioQuery->result();
      $this->CI->email->set_mailtype("html");
      $this->CI->email->from('tiago@tiagoluizweb.com.br', 'Tiago Luiz - ' . $usuarioResult[0]->nome);
      $this->CI->email->to($usuarioResult[0]->email);

      $this->CI->email->subject($tituloEmail . ' - ' . date("d-m-Y H:i:s"));
      $this->CI->email->message($html);
      $this->CI->email->send();
      
      if(!$this->CI->email->send()){
        $errorCounter++;
      }
    }
    if(count($this->usuarioId) == $errorCounter){
      return $data = array(
        'auth' => 1,
        'error' => 'Todos os e-mails tiveram erro no Envio',
      );
    }else{
      return $data = array(
        'auth' => 1,
        'error' => 'Alguns E-mails tiveram erro no envio',
      );
    }
      
     
  }
  public function editarTarefa(){
    $sql = "UPDATE `data__tarefa`
            SET `titulo` = '$this->titulo', `descricao` = '$this->descricao', `dataEntrega` = '$this->dataEntrega'  WHERE `id` = $this->id";

    $query = $this->CI->db->query($sql);
    
    if($query){
      return $data = array('auth' => 1);
    }else{
      return $data = array('auth' => 0);
    }
  }
}
?>
