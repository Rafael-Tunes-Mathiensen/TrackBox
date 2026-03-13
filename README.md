<div align="center">

# 🎵 TrackBox

**Sua Plataforma Inteligente para Organizar e Gerenciar Coleções de Música**

[![Status](https://img.shields.io/badge/Status-Em%20Desenvolvimento-orange?style=flat-square)](https://github.com)
[![PHP](https://img.shields.io/badge/PHP-8.0+-blue?style=flat-square&logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-blue?style=flat-square&logo=mysql)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)
[![Contributors](https://img.shields.io/badge/Contribuidores-Bem%20Vindos-brightgreen?style=flat-square)](CONTRIBUTING.md)

[🌐 Visitar Site](#) • [📚 Documentação](#documentação) • [🐛 Reportar Bug](#bugs) • [✨ Sugerir Recurso](#features)

---

</div>

## 📋 Índice

- [Sobre o Projeto](#sobre-o-projeto)
- [✨ Características](#-características)
- [🎯 Funcionalidades Principais](#-funcionalidades-principais)
- [🛠️ Tecnologias](#️-tecnologias)
- [📋 Pré-requisitos](#-pré-requisitos)
- [⚙️ Instalação](#️-instalação)
- [🚀 Uso](#-uso)
- [📁 Estrutura do Projeto](#-estrutura-do-projeto)
- [🔐 Segurança](#-segurança)
- [🗄️ Banco de Dados](#️-banco-de-dados)
- [🌐 Integração Discogs](#-integração-discogs)
- [📱 Responsividade](#-responsividade)
- [🤝 Contribuindo](#-contribuindo)
- [📝 Licença](#-licença)
- [👤 Autor](#-autor)
- [🆘 Suporte](#-suporte)

---

## 📖 Sobre o Projeto

**TrackBox** é uma aplicação web moderna e intuitiva desenvolvida para colecionadores de música que desejam manter sua coleção de CDs, LPs e BoxSets perfeitamente organizada e documentada.

O TrackBox permite que você:

- 🎵 **Cadastre seus discos** com informações detalhadas (artista, álbum, ano, gravadora, país de origem)
- 🖼️ **Armazene capas de discos** com upload de imagens
- 🌍 **Identifique edições internacionais** marcando discos importados e alertando sobre países conhecidos por falsificações
- ⭐ **Avalie condições** de discos e capas usando padrões internacionais (G, VG, E, Mint)
- 🔒 **Marque discos lacrados** para preservar raridades
- 💎 **Gerencie BoxSets especiais** com edições limitadas e numeradas
- 🔍 **Pesquise rapidamente** sua coleção com múltiplos filtros
- 📊 **Visualize estatísticas** sobre sua coleção com gráficos interativos
- 🤖 **Autocompletar cadastros** via integração com a API do Discogs
- ❤️ **Marque favoritos** para acessar rapidamente seus discos preferidos

---

## ✨ Características

### 🎨 Interface Moderna e Responsiva

- Design limpo e intuitivo com gradientes sofisticados
- Tema escuro otimizado para conforto visual
- Totalmente responsivo para desktop, tablet e mobile
- Animações suaves e transições fluidas
- Ícones Font Awesome integrados

### 🔐 Segurança Robusta

- Autenticação segura com hash de senha (bcrypt)
- Proteção contra CSRF (Cross-Site Request Forgery)
- Sanitização de entrada de dados
- Validação em cliente e servidor
- Prepared Statements para prevenir SQL Injection

### ⚡ Performance Otimizada

- Paginação eficiente de resultados
- Compressão de imagens
- Cache inteligente de dados
- Queries otimizadas com índices de banco de dados

### 🌐 Integração com Discogs

- Busca automática de discos por artista e álbum
- Preenchimento automático de campos
- Download de capas em alta qualidade
- Sincronização com banco de dados

### 📱 Multiplataforma

- Funciona em navegadores modernos (Chrome, Firefox, Safari, Edge)
- Aplicação web progressiva (PWA ready)
- Suporte offline planejado para futuras versões

---

## 🎯 Funcionalidades Principais

### 👤 Autenticação e Gerenciamento de Usuários
