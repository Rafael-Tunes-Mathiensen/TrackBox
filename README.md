# 🎵 TrackBox - Sistema de Gerenciamento de Coleção de Discos

> Um projeto desenvolvido para a disciplina de **Desenvolvimento Web**, com foco em criar uma solução prática para colecionadores de música.

---

# 📌 O Que é TrackBox?

**TrackBox** é uma **aplicação web** que permite catalogar, organizar e gerenciar sua coleção pessoal de **CDs, LPs e BoxSets** de forma simples e intuitiva.

Pense nele como um **"Spotify pessoal" para sua coleção física**.

Com ele você pode:

* Registrar discos com informações detalhadas
* Fazer upload de capas e imagens
* Buscar discos rapidamente
* Visualizar estatísticas da coleção
* Marcar discos como favoritos

---

# 🎯 Por Que Usar?

✅ **Controle Total**
Saiba exatamente o que existe na sua coleção.

✅ **Fácil de Usar**
Interface simples e intuitiva.

✅ **Busca Inteligente**
Encontre discos rapidamente utilizando filtros.

✅ **Visual Atraente**
Design moderno e responsivo.

✅ **Integração com Discogs**
Busca automática de discos e capas.

---

# 🔧 Tecnologias Utilizadas

| Tecnologia      | Uso                          |
| --------------- | ---------------------------- |
| **PHP 8.0+**    | Backend da aplicação         |
| **MySQL 8.0+**  | Banco de dados               |
| **HTML5**       | Estrutura das páginas        |
| **CSS3**        | Estilização e responsividade |
| **JavaScript**  | Interatividade               |
| **API Discogs** | Busca automática de discos   |

---

# ✨ Funcionalidades Principais

## 🎸 Cadastro de Discos

* Adicionar **CDs, LPs e BoxSets**
* Informações completas:

  * Artista
  * Álbum
  * Ano
  * Gravadora
  * País
* Upload de capas e imagens

---

## 🔍 Busca e Filtros

* Busca por **artista ou álbum**
* Filtro por **tipo de mídia**
* Filtro por **condição**
* Filtro por **país**
* Marcar discos como **favoritos**

---

## 📊 Dashboard

O painel principal mostra:

* Total de discos cadastrados
* Estatísticas por tipo de mídia
* Últimos discos adicionados
* Acesso rápido às principais funções

---

## 🤖 Busca Automática (Discogs)

Integração com a API do **Discogs**.

Fluxo:

1. Digite o nome do disco
2. A aplicação busca na API
3. Escolha um resultado
4. Os dados são preenchidos automaticamente
5. A capa é baixada automaticamente

---

## ⭐ Recursos Extras

* Marcar discos como **favoritos**
* Marcar discos **lacrados**
* Identificar **edições importadas**
* Avaliar condição do disco
* Gerenciar **edições limitadas de BoxSets**

---

# 💻 Como Instalar e Testar

## 1️⃣ Preparar o Ambiente

Você vai precisar de:

* **PHP 8.0+**
* **MySQL 8.0+**
* Navegador moderno (Chrome, Firefox, Edge, Safari)

Se não tiver:

### Windows

Instale o **XAMPP**

https://www.apachefriends.org/

### Mac

* XAMPP
* MAMP

https://www.mamp.info/

### Linux

```
sudo apt install php mysql-server
```

---

# 2️⃣ Baixar o Projeto

```
git clone https://github.com/seu-usuario/TrackBox.git
cd TrackBox
```

Ou baixe o **ZIP** do repositório.

---

# 3️⃣ Configurar Banco de Dados

## Opção A — phpMyAdmin

1. Acesse:

```
http://localhost/phpmyadmin
```

2. Clique em **Novo**
3. Nome do banco:

```
trackbox
```

4. Clique em **Importar**
5. Selecione:

```
sql/trackbox_db.sql
```

6. Execute.

---

## Opção B — Terminal

```
mysql -u root -p
```

Depois execute:

```
CREATE DATABASE trackbox;
USE trackbox;
SOURCE caminho/ate/sql/trackbox_db.sql;
EXIT;
```

---

# 4️⃣ Configurar Conexão com Banco

Abra:

```
config/database.php
```

Configure:

```
<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'sua-senha');
define('DB_NAME', 'trackbox');

?>
```

---

# 5️⃣ Criar Pasta de Upload

No terminal:

```
mkdir uploads/disks
```

Ou manualmente:

```
uploads/
   └── disks/
```

---

# 6️⃣ Iniciar o Servidor

## Usando XAMPP

1. Inicie **Apache**
2. Inicie **MySQL**

Acesse:

```
http://localhost/TrackBox
```

---

## Usando PHP interno

```
php -S localhost:8000
```

Depois acesse:

```
http://localhost:8000
```

---

# 7️⃣ Primeiro Acesso

1. Abra a aplicação
2. Clique em **Cadastrar-se**
3. Crie sua conta

Dados necessários:

* Nome
* Email
* Senha (mínimo 8 caracteres)

Depois faça **login**.

---

# 🚀 Como Usar

## Cadastrar Disco

1. Clique em **Cadastrar Disco**
2. Escolha o tipo:

   * CD
   * LP
   * BoxSet
3. Preencha os dados
4. Faça upload da capa
5. Salve

---

## Usar Busca Discogs

Digite algo como:

```
Pink Floyd Dark Side
```

Escolha um resultado e os dados serão preenchidos automaticamente.

---

## Pesquisar Discos

Na página **Minha Coleção** você pode:

* Buscar por artista
* Buscar por álbum
* Filtrar por tipo
* Filtrar por país
* Filtrar por condição

---

# 📂 Estrutura do Projeto

```
TrackBox/

index.php
login.php
register.php
dashboard.php

register_disk.php
edit_disk.php
disk_details.php
search_disks.php

config/
   database.php
   api_keys.php

includes/
   header.php
   footer.php
   functions.php

api/
   discogs_search.php
   toggle_favorite.php

assets/
   css/
   js/
   img/

uploads/
   disks/

sql/
   trackbox_db.sql
```

---

# 🔐 Segurança Implementada

✔ Senhas criptografadas (**bcrypt**)
✔ Proteção **CSRF**
✔ Validação contra **SQL Injection**
✔ Sessões seguras
✔ Isolamento de dados por usuário

---

# 🌐 Integração Discogs

Discogs é um enorme banco de dados musical com **milhões de discos catalogados**.

TrackBox utiliza essa API para:

* buscar discos
* preencher dados automaticamente
* baixar capas

A integração **não é obrigatória**.

---

# 🎓 Projeto Acadêmico

Este projeto foi desenvolvido para a disciplina de **Desenvolvimento Web**.

Objetivos de aprendizado:

* PHP Backend
* MySQL
* HTML e CSS
* JavaScript
* Integração com APIs
* Segurança web
* Estruturação de projetos reais

---

# ⚠️ Troubleshooting

## Erro ao conectar ao banco

* Verifique se **MySQL está rodando**
* Confirme dados em `config/database.php`
* Verifique se o banco `trackbox` existe

---

## Upload de imagens não funciona

Verifique se a pasta existe:

```
uploads/disks
```

E se tem permissão de escrita.

---

## Erro 404

Confirme que o servidor está rodando e que a URL está correta.

---

# 📞 Suporte

Se encontrar problemas:

* Abra uma **Issue no GitHub**
* Consulte a documentação
* Entre em contato com o desenvolvedor

---

# 📝 Licença

Este projeto está sob a licença **MIT**.

---

# 👨‍💻 Autor

Desenvolvido por **Rafael Tunes Mathiensen**

TrackBox © 2025
