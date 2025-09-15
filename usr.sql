create database users;
use users;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cpf VARCHAR(14) NOT NULL,
    usuario VARCHAR(100) NOT NULL,
    telefone VARCHAR(15) NOT NULL,
    email VARCHAR(100) NOT NULL,
    senha VARCHAR(255) NOT NULL
);
CREATE TABLE redefinir_senha (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  token VARCHAR(64) NOT NULL,
  expiracao DATETIME NOT NULL,
  usado BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
CREATE USER 'admin'@'localhost' IDENTIFIED BY 'admin';
GRANT ALL PRIVILEGES ON users.* TO 'admin'@'localhost';
FLUSH PRIVILEGES;
SELECT * FROM usuarios;
DESCRIBE usuarios;
ALTER TABLE usuarios
ADD COLUMN token_recuperacao VARCHAR(255),
ADD COLUMN token_expira DATETIME;
INSERT INTO usuarios (usuario, email, cpf, telefone, senha)
VALUES ('usuario_teste', 'teste@teste.com', '12345678900', '11999999999', 
        '$2y$10$Zq6nR0Q3h/vJY2HklT1Z8uOSqU3Yw.LK05zX4c53hHlDbKjEOYOTK');
ALTER TABLE usuarios ADD saldo DECIMAL(10,2) DEFAULT 0;
CREATE TABLE saldos_usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  tipo ENUM('entrada', 'saida') NOT NULL,
  valor DECIMAL(10,2) NOT NULL,
  descricao VARCHAR(255),
  data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
CREATE TABLE frete (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cep_origem VARCHAR(9) NOT NULL,
    cep_destino VARCHAR(9) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    prazo INT NOT NULL
);

