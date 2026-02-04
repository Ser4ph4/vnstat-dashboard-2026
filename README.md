#☁️Modern dashboard for traffic statistics using VNSTAT
![Logo VnStat Panel](https://cdn.jsdelivr.net/gh/ser4ph4/ser4ph4.github.io/images/logo_vnstat_panel.png)

# 🚀 VnStat Dashboard Modern

Uma interface web moderna, responsiva e elegante desenvolvida com PHP e React para visualização de estatísticas de tráfego de rede provenientes do vnStat. O painel transforma os dados brutos em gráficos interativos de alta qualidade, facilitando o monitoramento de consumo de dados e métricas de hardware.

## ✨ Funcionalidades

*   **Dashboards Interativos**: Visualização detalhada de tráfego por Hora, Dia, Mês e Ano através de gráficos de área, barras e linhas.
*   **Métricas do Sistema**: Acompanhamento em tempo real do uso de memória RAM, carga do sistema (Load Average) e Uptime.
*   **Segurança Robusta**: Sistema de autenticação com proteção de sessão e validação de credenciais via `password_hash`.
*   **Design Premium**: Interface escura com efeito Glassmorphism, ícones animados e design totalmente responsivo com Tailwind CSS.
*   **Busca Inteligente**: Algoritmo no frontend que localiza automaticamente o arquivo de dados correto baseado nas interfaces de rede disponíveis no servidor.
*   **Performance**: Carregamento otimizado com monitoramento de latência do JSON e tempo de carregamento da página.

## 🛠️ Tecnologias Utilizadas

*   **Frontend**: React.js, Tailwind CSS, Recharts (Gráficos), Font Awesome.
*   **Backend**: PHP (API de estatísticas e gerenciamento de sessões).
*   **Dados**: vnStat (Service).

## 📋 Pré-requisitos

Certifique-se de ter os seguintes componentes instalados no seu servidor Linux:

*   **vnStat instalado e configurado**:

    ```bash
    sudo apt update && sudo apt install vnstat
    ```

*   **Servidor Web (Apache/Nginx) com PHP 7.4 ou superior**.

*   **Permissões**: O usuário do servidor web deve ter permissão de leitura em `/proc/meminfo` e `/proc/uptime` para as estatísticas de sistema.

## 🚀 Instalação e Configuração

### 1. Preparação dos Arquivos

Clone o repositório no seu diretório web e garanta que a estrutura de pastas inclua um diretório chamado `pass/` para as credenciais.

### 2. Configurar a Interface de Rede

Edite o arquivo `update_data.php` e defina a variável `$interface` com o nome da sua interface de rede (ex: `eth0`, `wlan0`).

```php
$interface = 'sua_interface_aqui'; //
```

### 3. Configurar Credenciais

Crie o arquivo `pass/credentials.php` (este arquivo é chamado pelo `login.php`) com o seguinte formato:

```php
<?php
return [
    'username' => 'seu_usuario',
    'password_hash' => password_hash('sua_senha_segura', PASSWORD_DEFAULT)
];
```

### 4. Automatizar a Atualização

Adicione uma tarefa no Cron para gerar o JSON de tráfego automaticamente a cada 5 minutos:

```bash
crontab -e
```

Adicione a linha:

```
*/5 * * * * /usr/bin/php /var/www/html/projeto/update_data.php
```

## 📂 Estrutura do Projeto

| Arquivo           | Função                                                              |
| :---------------- | :------------------------------------------------------------------ |
| `index.php`       | Núcleo do frontend em React e estrutura visual do painel.           |
| `api_stats.php`   | API interna que fornece dados de CPU, RAM e integridade do banco vnStat. |
| `update_data.php` | Exportador que converte a saída do comando `vnstat --json` em arquivo físico. |
| `login.php`       | Interface de acesso seguro com validação de hash.                   |
| `config.php`      | Gerenciamento de sessões e configurações de segurança global.       |
| `logout.php`      | Encerramento seguro de sessão.                                      |

## Screenshot:
![Sumary Screen](https://cdn.jsdelivr.net/gh/ser4ph4/ser4ph4.github.io/images/Sumary.png)
![Sumary Screen](https://cdn.jsdelivr.net/gh/ser4ph4/ser4ph4.github.io/images/days.png)
![Sumary Screen](https://cdn.jsdelivr.net/gh/ser4ph4/ser4ph4.github.io/images/login.png)

## 🔒 Detalhes de Segurança

*   **Proteção de Sessão**: O sistema utiliza `session_start` com nomes personalizados para evitar conflitos e sequestro de sessão.
*   **Tratamento de Erros**: Configurações de `ini_set('display_errors', 0)` para impedir vazamento de informações do servidor em produção.
*   **Autenticação**: O login é validado contra um hash seguro, impedindo o armazenamento de senhas em texto plano.

## 📄 Licença

Este projeto está sob a licença MIT.
