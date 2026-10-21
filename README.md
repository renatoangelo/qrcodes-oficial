# QR codes dinâmicos

Sistema de QR codes que podem mudar de destino sem reimprimir. Cada QR code aponta para um endereço fixo
deste sistema (`https://qr.cliente.com.br/alias`), que redireciona para o destino cadastrado no painel.

Projeto do Renato Angelo, feito para ser instalado em vários clientes, cada um no próprio subdomínio,
**sem banco de dados**: tudo fica em arquivos na pasta `dados/`.

> Nasceu do sistema de QR codes da HBs Service (repositório privado `hbsserviceapp/hbsqrcode`, que segue
> separado e não usa este). Esta pasta é a versão oficial e genérica, para outros clientes.

---

## O que o sistema faz

- **Redirecionamento:** `/alias` vai para o destino cadastrado (302, sem cache). QR code pausado, alias que não
  existe ou destino inválido vão para o **destino padrão** da instalação; se nem ele existir, aparece uma
  página simples de "não encontrado". Nunca entra em laço.
- **Painel** em `/admin/` com login próprio e vários usuários:
  - perfis **Administrador** (tudo) e **Usuário** (QR codes, dashboard e histórico; sem Usuários e Ajustes);
  - não deixa remover nem rebaixar o último administrador, nem a pessoa desativar a si mesma.
- **QR codes:**
  - criar, editar destino e observações, pausar e reativar;
  - apagar com dupla confirmação (confirmar e digitar o alias);
  - o **alias não muda depois de criado**, porque o QR impresso depende dele;
  - histórico de destinos de cada QR.
- **Imagens:** PNG para tela (~600 px), PNG para impressão (~3000 px) e SVG vetorial. Correção de erro nível Q,
  preto no branco, sem logo no meio (decisão de projeto: leitura garantida).
- **Dashboard:**
  - números de hoje, dos últimos 30 dias, do mês e desde o início;
  - leituras por dia (30 dias) e por mês (12 meses);
  - por QR code e por aparelho (30 dias), e por hora do dia;
  - filtro por QR code; cada gráfico tem a versão em tabela.
- **Histórico** de quem mudou o quê e quando (logins escondidos por padrão).
- **Ajustes:** nome do painel, destino padrão, endereço do sistema e fuso horário.
- **Privacidade:** cada leitura grava só data e hora, alias, tipo de aparelho (celular, tablet, computador ou
  robô) e a forma de entrada. **Sem IP, sem navegador completo, sem cookie.** Robôs (pré-visualização de link
  do WhatsApp, buscadores) são gravados, mas não entram nas contagens.
- **Compatível com o sistema antigo** (`/qrcode/index.php?target=alias`): o endereço antigo continua atendido
  para sempre, e o `contador.json` antigo é importado sozinho na primeira execução (ver "Vindo do sistema antigo").

## Requisitos do servidor

- PHP **8.1 ou mais novo** (desenvolvido e testado em 8.4), com a extensão **GD** para o PNG. Sem GD, o SVG
  continua funcionando.
- Apache ou LiteSpeed com `mod_rewrite` (o padrão do cPanel).
- HTTPS no subdomínio (AutoSSL do cPanel).

---

## Instalar num cliente (pelo Git do cPanel)

O repositório é clonado **direto na pasta do subdomínio**. Não precisa de deploy: para atualizar, basta
**Update from Remote**. Se o repositório no GitHub for público, o servidor do cliente não precisa de chave SSH
(clona pelo endereço `https://`).

1. **Criar o subdomínio:** cPanel → **Domínios** → **Criar um novo domínio**.
   - Domínio: `qr.cliente.com.br`. Escolha bem o nome: depois de imprimir, ele não muda mais.
   - **Desmarque** "Compartilhar a raiz do documento". Anote a pasta (ex.: `qr.cliente.com.br`).
   - A pasta precisa ficar **vazia**, porque o Git só clona em pasta vazia. Se o cPanel criou algum arquivo
     nela, apague.
2. **Conferir o PHP:** cPanel → **MultiPHP Manager**. O subdomínio deve estar em 8.1 ou mais (de preferência 8.4).
3. **Conferir o HTTPS:** cPanel → **SSL/TLS Status**. Se não tiver cadeado, clique em **Run AutoSSL** e espere.
4. **Clonar:** cPanel → **Git Version Control** → **Create**.
   - Clone URL: o endereço `https://github.com/...` deste repositório.
   - Repository Path: **a pasta do subdomínio** (ex.: `qr.cliente.com.br`).
   - Repository Name: o que quiser (ex.: `qr-cliente`).
5. **Instalar:**
   - Abra `https://qr.cliente.com.br/admin/`. Aparece a tela de instalação, pedindo um código.
   - No **Gerenciador de Arquivos**, recarregue a pasta do subdomínio. Foi criada a pasta `dados/`. Abra
     `dados/codigo-de-instalacao.php` (botão direito → **Ver**) e copie os 10 caracteres da **segunda linha**.
   - Preencha: código, nome, login, senha (mínimo de 10 caracteres, sem conter o login), endereço do sistema
     (já vem preenchido; confira o `https`) e **destino padrão** (ex.: o site do cliente).
   - O código é apagado sozinho depois da instalação.
6. **Personalizar:** em **Ajustes**, troque o nome do painel (ex.: "QR codes Cliente").
7. **Testar:** crie um QR `teste`, baixe o PNG, escaneie com o celular e veja a leitura no Dashboard. Depois,
   apague ou pause.

### Atualizar um cliente

cPanel → **Git Version Control** → **Manage** → **Pull or Deploy** → **Update from Remote**. Só isso.
A pasta `dados/` não está no Git, então QR codes, usuários e leituras não são tocados.

### Instalar sem Git (pacote .zip)

1. No computador, gere o pacote: `sh ferramentas/pacote.sh`. Ele cria `qrcodes-<versão>.zip` com o
   último commit, sem `ferramentas/` e sem os arquivos do Docker.
2. Siga os passos 1 a 3 acima.
3. No Gerenciador de Arquivos, envie o .zip para a pasta do subdomínio e use **Extract**. O `index.php`
   precisa ficar direto na pasta, não dentro de outra.
4. Apague o .zip e siga do passo 5.

Para atualizar, extraia o pacote novo por cima, substituindo os arquivos.

## Vindo do sistema antigo (`/qrcode/index.php?target=...`)

Na primeira execução, se ainda não existir `dados/destinos.php`, o sistema importa o `contador.json` do
sistema antigo, **uma vez só**. Ele procura em:

1. `importar/contador.json`: uma cópia colocada à mão; depois de importada, é renomeada.
2. `qrcode/admin/contador.json`: o arquivo do sistema antigo, quando o novo é instalado na mesma pasta.
   **Nunca é alterado**, para o sistema antigo poder voltar se for preciso.

Cada entrada vira um QR code com o mesmo alias (inclusive maiúsculas) e o mesmo destino, no formato
**antigo**, para a imagem gerada ser idêntica à já impressa. As leituras antigas entram no total, mas não nos
gráficos, porque o sistema antigo não guardava a data. A entrada `default` vira o destino padrão.

Nesse caso a pasta do subdomínio não está vazia, então não dá para clonar nela. A saída é clonar em
`repositories/<nome>` e copiar no deploy, com um `.cpanel.yml` próprio daquele servidor (é como está na HBs):

```yaml
---
deployment:
  tasks:
    - export DEPLOYPATH=/home/<usuario-cpanel>/<pasta-do-subdominio>/
    - /bin/cp -R index.php .htaccess robots.txt admin app qrcode importar $DEPLOYPATH
```

## Senha esquecida, backup e segurança

- **Esqueceu a senha do único administrador:** apague `dados/usuarios.php` pelo Gerenciador de Arquivos e abra
  `/admin/` de novo. A tela de instalação volta com um código novo; QR codes e leituras não são afetados.
- **Backup:** tudo o que importa está em `dados/`. O JetBackup do cPanel já inclui essa pasta. Para
  restaurar, basta colocá-la de volta.
- **Segurança:**
  - Os arquivos de `dados/` começam com `<?php exit; ?>`, então abertos pelo navegador não mostram nada.
  - `app/`, `dados/`, `importar/`, `ferramentas/`, a pasta `.git`, arquivos ocultos, `.md`, `.yml`, `.sh`, `.zip`,
    `.json` e `.bak` são bloqueados no `.htaccess`. A exceção é `.well-known`, por causa do certificado HTTPS.
  - Senhas com `password_hash`.
  - Login bloqueado por 15 minutos depois de 5 tentativas erradas (contadas por login e por IP, guardados só
    como hash com uma chave da instalação).
  - Sessão de 2 horas, cookie HttpOnly, Secure e SameSite=Lax, válido só em `/admin/`.
  - CSRF em todos os formulários.
  - CSP rígida (`default-src 'self'`, sem script nem estilo embutido) e proibição de iframe.
  - O destino só aceita `http://` e `https://`.
  - O código ser público não enfraquece nada disso: os segredos (chave da instalação, código de instalação,
    senhas) são criados em cada servidor, dentro de `dados/`. O cuidado é manter os clientes **atualizados**,
    porque uma falha descoberta valeria para todos.

---

## Para quem vai mexer no código

### Testar no computador

Com o Docker Desktop aberto, na pasta do projeto:

```bash
docker compose up -d --build
```

Depois abra http://localhost:8092/admin/. No localhost, o código de instalação aparece na própria tela.
A porta é 8092 para não bater com outros projetos (o sistema da HBs usa a 8090).

### Pastas

```
index.php              ← atende /alias
qrcode/index.php       ← atende o endereço antigo /qrcode/index.php?target=alias
.htaccess              ← reescrita /alias → index.php?a=alias e bloqueios
admin/                 ← painel (uma página PHP por tela; _inicio.php abre todas)
admin/assets/          ← painel.css (claro e escuro automáticos) e painel.js (sem bibliotecas)
app/                   ← código, bloqueado ao navegador
  bootstrap.php        ← constantes, carrega as classes, fuso, importação
  Armazem.php          ← leitura e gravação segura dos arquivos de dados
  Destinos.php         ← QR codes (alias, destino, status, formato)
  Acessos.php          ← registro das leituras (uma linha por leitura, por mês)
  Estatisticas.php     ← contagens do dashboard
  Redirecionador.php   ← decide para onde cada leitura vai
  Usuarios.php, Sessao.php, Ajustes.php, Historico.php, Importador.php, QR.php, funcoes.php
  visao/               ← topo, rodapé e gráficos (SVG feito no servidor)
  phpqrcode/           ← biblioteca de QR code (LGPL; ajustada para PHP 8.4, sem cache em disco)
dados/                 ← criada na primeira visita; nunca vai para o Git nem para o pacote
importar/              ← onde colocar um contador.json antigo, se for o caso
ferramentas/           ← Dockerfile do ambiente local e pacote.sh
```

### Como os dados são guardados (`dados/`)

| Arquivo | Conteúdo |
| --- | --- |
| `destinos.php` | QR codes: url, status (`ativo`/`pausado`), obs, formato (`curto`/`antigo`), anteriores, criado/alterado em e por |
| `usuarios.php` | Login, nome, perfil, hash da senha, ativo, último acesso |
| `ajustes.php` | nome, padrao, base, fuso, segredo |
| `acessos/AAAA-MM.php` | Uma linha por leitura: `data hora ⇥ alias ⇥ aparelho ⇥ entrada` |
| `historico.php` | Uma linha JSON por ação: quando, usuário, ação, alvo, detalhes |
| `tentativas.php` | Falhas de login recentes (hash) |
| `codigo-de-instalacao.php` | Só até a instalação |

- **Formato dos arquivos:** todo arquivo começa com `<?php exit; ?>`.
- **Gravação segura:** a gravação (`Armazem::atualizar`) usa trava (`flock`), arquivo temporário + `rename` e
  mantém uma cópia `.bak`. Se a leitura falhar, não grava por cima.
- **Leituras:** são só acrescentadas (`Armazem::acrescentar`), com trava.
- **Teste de carga:** 1.400 leituras simultâneas com 200 gravações no painel ao mesmo tempo, sem nenhuma perda.
- **Por que isso importa:** foi exatamente uma gravação sem trava que zerou o `contador.json` do sistema antigo
  e derrubou os QR codes.

### Decisões que não mudam

- **Sem banco de dados e sem dependências:** só PHP. Instala clonando ou descompactando.
- **O alias é imutável.** Um QR do formato `antigo` gera sempre a mesma imagem do impresso.
  QR novo é sempre `curto` (`/alias`).
- **Aliases:**
  - minúsculas, números e hífen, até 60 caracteres;
  - os importados podem ter maiúsculas;
  - reservados: `admin`, `app`, `dados`, `importar`, `qrcode`, `ferramentas`, `assets`, `index`, `robots.txt`,
    `favicon.ico`.
- **Pausar em vez de apagar.** Apagar existe, mas com dupla confirmação.
- **O destino padrão não pode ser o próprio sistema** (criaria laço).
- **Nada de script ou estilo embutido nas páginas:** a CSP bloqueia. Medidas de gráfico vão em atributos de SVG.
- **Nada que identifique quem leu o QR code.**
- **Versão:** `QR_VERSAO` em `app/bootstrap.php`. Suba a versão a cada mudança de CSS ou JS, porque ela renova o
  cache do navegador.

### Trabalho com o Renato (instruções para o Claude)

- Conversar em português. Explicar o que foi feito e por quê, em linguagem simples.
- **Git: não executar nada que altere o repositório** (init, add, commit, push). Entregar os comandos para o
  Renato rodar, num único bloco, começando por `cd` com o caminho completo:
  `cd /Users/renatoangelo/Projects/qrcode-oficial`, `git add .`, `git commit -m "..."`, `git push origin main`.
  Comandos de leitura (`status`, `diff`, `log`) podem ser usados.
- Mostrar o plano antes de criar ou reestruturar arquivos; ajustes pontuais pedidos podem ser feitos direto.
- Testar no navegador (computador e celular) antes de dizer que está pronto, e dizer o que não deu para testar.
- Nunca inventar dados de cliente (endereços, destinos, nomes).

### Pendências

- [ ] Criar o repositório no GitHub pessoal do Renato e definir se fica **público** (nome e usuário a definir).
- [ ] Escolher a licença do código próprio (ex.: MIT para liberar o reaproveitamento, ou "todos os direitos
      reservados"). A biblioteca `app/phpqrcode` é LGPL e o arquivo `app/phpqrcode/LICENSE` precisa continuar no projeto.
- [ ] Primeira instalação real fora da HBs (planejada: `qr.publicitariodigital.com.br`), seguindo
      "Instalar num cliente". Conferir no servidor: o bloqueio da pasta `.git` e dos arquivos `.md`/`.yml`, e o
      download do PNG (extensão GD).
