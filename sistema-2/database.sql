CREATE DATABASE clinica;
USE clinica;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    login VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('medico', 'secretaria') NOT NULL,
    crm VARCHAR(20)
);

CREATE TABLE pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    rg VARCHAR(20),
    data_nascimento DATE NOT NULL,
    tipo_consulta ENUM('particular', 'plano') NOT NULL,
    plano_saude VARCHAR(50),
    outro_plano VARCHAR(100),
    rua VARCHAR(100) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    complemento VARCHAR(100),
    cidade VARCHAR(100) NOT NULL,
    estado CHAR(2) NOT NULL,
    cep VARCHAR(9) NOT NULL,
    telefone VARCHAR(15) NOT NULL,
    email VARCHAR(100),
    foto LONGBLOB,
    foto_tipo VARCHAR(30),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    data_consulta DATE NOT NULL,
    horario TIME NOT NULL,
    tipo_atendimento ENUM('consulta', 'retorno', 'mapeamento') NOT NULL,
    valor DECIMAL(10,2),
    status ENUM('agendado', 'atendido', 'faltou', 'cancelado') NOT NULL DEFAULT 'agendado',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    medico_id INT,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
    FOREIGN KEY (medico_id) REFERENCES usuarios(id)
);

DROP TABLE IF EXISTS prontuarios;
CREATE TABLE prontuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    data_consulta DATETIME NOT NULL,
    acuidade_od VARCHAR(20),
    acuidade_oe VARCHAR(20),
    tonometria_od VARCHAR(20),
    tonometria_oe VARCHAR(20),
    biomicroscopia TEXT,
    fundoscopia TEXT,
    conduta TEXT,
    observacoes TEXT,
    medico_id INT NOT NULL,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
    FOREIGN KEY (medico_id) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS dias_sem_atendimento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data DATE NOT NULL UNIQUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserir usuários iniciais
INSERT INTO usuarios (nome, login, senha, tipo) VALUES 
('José Manoel Lopes', 'JoseML', '$2y$10$YourHashedPasswordHere', 'medico'),
('Rosa', 'Rosa', '$2y$10$YourHashedPasswordHere', 'secretaria');

-- Atualizar a tabela agendamentos para incluir medico_id
ALTER TABLE agendamentos ADD COLUMN IF NOT EXISTS medico_id INT,
ADD COLUMN IF NOT EXISTS valor DECIMAL(10,2),
ADD FOREIGN KEY (medico_id) REFERENCES usuarios(id);

-- Atualizar a tabela prontuarios para incluir todos os campos necessários
ALTER TABLE prontuarios 
ADD COLUMN od_esferico VARCHAR(20) AFTER observacoes,
ADD COLUMN od_cilindrico VARCHAR(20) AFTER od_esferico,
ADD COLUMN od_eixo VARCHAR(20) AFTER od_cilindrico,
ADD COLUMN od_dnp VARCHAR(20) AFTER od_eixo,
ADD COLUMN od_altura VARCHAR(20) AFTER od_dnp,
ADD COLUMN oe_esferico VARCHAR(20) AFTER od_altura,
ADD COLUMN oe_cilindrico VARCHAR(20) AFTER oe_esferico,
ADD COLUMN oe_eixo VARCHAR(20) AFTER oe_cilindrico,
ADD COLUMN oe_dnp VARCHAR(20) AFTER oe_eixo,
ADD COLUMN oe_altura VARCHAR(20) AFTER oe_dnp,
ADD COLUMN adicao VARCHAR(20) AFTER oe_altura,
ADD COLUMN obs_receita TEXT AFTER adicao;

-- Adicionar a coluna foto na tabela pacientes
ALTER TABLE pacientes ADD COLUMN foto LONGBLOB;

-- Adicionar coluna data_criacao se não existir
ALTER TABLE agendamentos 
ADD COLUMN IF NOT EXISTS data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Garantir que a estrutura da tabela está correta
ALTER TABLE agendamentos 
MODIFY COLUMN data_consulta DATE NOT NULL,
MODIFY COLUMN horario TIME NOT NULL,
MODIFY COLUMN tipo_atendimento ENUM('consulta', 'retorno', 'mapeamento') NOT NULL,
MODIFY COLUMN valor DECIMAL(10,2),
MODIFY COLUMN status ENUM('agendado', 'atendido', 'faltou', 'cancelado') NOT NULL DEFAULT 'agendado';

-- Atualizar no banco de dados se necessário
UPDATE usuarios 
SET crm = 'CRM-MG 12965' 
WHERE nome = 'José Manoel Lopes' 
AND tipo = 'medico'; 