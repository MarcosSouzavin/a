
CREATE TABLE IF NOT EXISTS produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  description TEXT,
  image VARCHAR(255),
  sizes JSON,
  adicionais JSON
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO produtos (name, description, image, sizes, adicionais) VALUES
('5 queijos', 'Molho de tomate especial, mussarela ralada, provolone, parmessão, catupiry, cheddar, oregano e azeitonas', 'img/pizzas/5_queijos.png',
  JSON_ARRAY(JSON_OBJECT('name','Pequena','price',37.00), JSON_OBJECT('name','Média','price',78.50), JSON_OBJECT('name','Grande','price',105.00)),
  JSON_ARRAY(JSON_OBJECT('id','bacon','name','Bacon','price',4.00), JSON_OBJECT('id','catupiry','name','Catupiry','price',5.00))
),
('3 Porquinhos', 'Molho de tomate especial, mussarela, lombo canadense, calabresa fatiada, bacon, oregano e azeitonas', 'img/pizzas/3_porcos.png',
  JSON_ARRAY(JSON_OBJECT('name','Pequena','price',37.00), JSON_OBJECT('name','Média','price',78.50), JSON_OBJECT('name','Grande','price',105.00)),
  JSON_ARRAY(JSON_OBJECT('id','bacon','name','Bacon','price',4.00), JSON_OBJECT('id','azeitonas','name','Azeitonas','price',2.00))
),
('Bauru', 'Molho de tomate especial, mussarela ralada, coxão mole em tiras, tomate em rodelas, orégano e azeitonas', 'img/pizzas/bauru.png',
  JSON_ARRAY(JSON_OBJECT('name','Pequena','price',40.00), JSON_OBJECT('name','Média','price',78.50), JSON_OBJECT('name','Grande','price',105.00)),
  JSON_ARRAY(JSON_OBJECT('id','extra_carne','name','Extra Carne','price',6.00))
);