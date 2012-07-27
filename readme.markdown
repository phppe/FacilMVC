# O que é o Facil MVC? #

* Um framwork opensource para auxiliar desenvolvedores PHP a iniciar e manter seus projetos Web.
* Promove a separação de camadas MODELO, VISAO e CONTROLADOR.
* Realiza mapeamento de URLs para chamadas a métodos das classes (módulos) da camada controladora
* Projetos pequenos podem conter apenas métodos na classe \controlador\Home
* Projetos médios podem conter mais classes que herdem de \controlador\Modulo
* Projetos maiores podem dividir o controlador em diretórios para melhor organização de seus módulos
* Suporta inferência de ambiente na própria URL
* Emabasado no framework HTML5 Boilerplate, realiza todas as suas boas práticas e utiliza a biblioteca MinifyJs para reduzir JS e CSS on the fly sem você se preocupar.
* Suporta internacionalização de modo simples eficaz.
* Suporta múltiplos ambientes a partir de arquivos de configuração e já traz os ambientes desenvolvimento, produção e testes como sugestão
* Suporta adição de novos módulos através de plugins em LIB

# Pre-requisitos #

* PHP 5.3.0+
* Apache + mod_rewrite
* Arquivo .htaccess habilitado

# Como surgiu? #

Este projeto foi inicialmente concebido em 2008, por Jose Berardo, diretor da Especializa Treinamentos (http://www.especializa.com.br),
para servir de sugestão de organização de diretórios para os primeiros projetos de seus alunos.

A partir daí, algum código foi escrito para ajudar a ensinar técnicas como:
* Reescrita de URLs
* Instrospecção (reflection)
* Ajustes de configurações do php.ini
* Expressões regulares
* Internacionalização

E uma série de outras que foram surgindo.

Ao longo de anos, alguns ex-alunos e amigos passaram a adotar este framework nas empresas em que 
desenvolviam e constantemente relatavam incrementos e melhorias no código.

# Quem o matém hoje? #

Todo o código do FacilMVC foi completamente cedido à comunidade PHP Pernambuco

* http://groups.google.com/group/phppernambuco
* https://github.com/phppe/
* https://www.facebook.com/groups/458218590859250/

Seu número de colaboradores não pára de crescer e seu desenvolvimento está todo organizado no GitHub.


# Como começo a trabalhar com o Fácil MVC? #

## Primeiro clone este projeto do GitHub ##

Este projeto está em franco desenvolvimento, portanto:

Execute o comando:
$ git clone https://github.com/phppe/FacilMVC.git
Ou utilize a ferramenta que achar mais adequada para clonar a última versão do GitHub

## Inicie a partir do controlador ##

Vá ao arquivo Home.php no diretório controlador e confira seus métodos index() e phpinfo().

O primeiro já realiza uma ação Facil::despachar() onde é passado o nome (sem a não obrigatória extensão)
do arquivo de template que será utilizado para devolver alguma resposta ao cliente.

O segundo, phpinfo(), apenas exibe a função phpinfo() para informar as configurações do servidor.
Ele deve ser excluido, mas serve apenas para mostrar que você pode chamá-lo no browser ao digitar uma url como:
http://meuservidor/phpinfo ou
http://meuservidor/home/phpinfo

Note que o nome do módulo "Home" não é necessário, assim como se você não digitar nenhum caminho,
o método index será o executado.

Você pode conferir e alterar os valores padrão no arquivo padrao.ini do diretório controlador.

## Como integrar com a visão? ##

Antes de qualquer chamada a Facil::despachar(), é possível invocar Facil::setar('variavel', 'valor').
Esta operação vai registrar uma variável que pode ser facilmente recuperada nos arquivos da visão
(presentes no diretório visao/default) apenas usando <?=$variavel?>

O diretório padrão para os arquivos da visão é o default, abaixo de visao. No entanto,
isso pode ser modificado no arquivo padrao.ini de config.
É possível mudar também a extensão padrão desses arquivos bem como desrespeitar esse padrão se
a extensão for fornecida no método despachar. Por exemplo: Facil::despachar('tela.php');

Projetos podem conter múltiplas templates.
Por exemplo, sistemas que possam carregar temas diferentes escolhidos pelos próprios usuários,
podem se valer da instrução Facil::setTemplate('nova_template'), onde 'nova_template' seria
um diretório abaixo de visao.
Realize este comando antes de Facil::despachar(). 

### Internacionalização ###

Para adicionar mensagens sensíveis a traduções para mais de um idioma escreva diretamente
nos arquivos da visão, algo como:

<p>{$principal.teste}</p>

Ao despachar, o controlador vai no diretório config/i18n buscar pelo arquivo principal_pt_br.ini, 
onde pt_br é obtido a partir do cabeçalho Http Accept-Language.
Ele então substitui o texto original pelo valor da variável teste.
É possível agrupar variáveis nesses arquivos com algo como:

[tela_inicial]
bemvindo = Seja Bem-Vindo ao Fácil MVC

Para obter esta mensagem, basta inserir em algum arquivo da visão, o texto:

{$principal.tela_inicial.bemvindo}

É possível definir um idioma a partir de qualquer fonte, em vez de sempre se valer do Accept-Language.
Para tanto, antes de chamar Facil::despachar(), execute Facil::setIdioma('en') onde 'en' seja
o sufixo de locale dos arquivos em config/i18n.

### Gestão de recursos estáticos ###

Imagens devem ser salvas em visao/default/img e assim por diante para Javascript (js) e CSS (css).
Há um diretório recursos para outros tipos de arquivos.

Para linkar esses arquivos nos arquivos da visão, preocupe-se apenas com seu caminho relativo
ao diretório onde está o seu arquivo .html, o controlador vai entender que ele foi incluido 
na raiz (index.php) e substituir seu caminho relativo pelo caminho real até este diretório.

Arquivos JS e CSS podem ser reduzidos (processo conhecido por minify) automaticamente no momento
em que forem requisitados pelos clientes. Para que isto ocorra, retire o comentário da
linha 129 do arquivo .htacess da raiz do projeto. 
Esta linha encaminha ao PHP todas as requisições aos recursos estáticos, permitindo que
ela possa reduzir o conteúdo de JS e CSS em três níveis (configuráveis nos arquivos de ambiente 
do diretório config).

### Combos de JS e CSS ###

Uma prática recomendada por empresas como Google e Yahoo, é o de unificar arquivos js e css, evitando
excesso de requisições HTTP e consequentemente melhorando o desempenho final.

O Facil MVC traz duas maneiras de facilmente unir arquivos, se você criar um arquivo como:

js/scripts.combo.js

Onde js é o caminho relativo do combo, scripts é um nome que você escolhe e .combo.js é uma extensão
válida (a outra é .combo.css). 
Este arquivo deverá carregar os nomes de outros arquivos que serão todos incluídos na resposta.

A segunda maneira de combinar arquivos em um só é simplesmente chamar uma URL como:

js/libs/libs.js

Onde js é o caminho para o arquivo. libs é um subdiretório que contenha os arquivos a serem
combinados e libs.js não seja um arquivo existente, mas uma repetição do nome do diretório (no caso libs)
com a extensão .js ou .css.

Fazendo isso, o Facil MVC irá combinar todos os arquivos presentes no diretório libs, sem você 
precisar sequer criar um arquivo de combinação.

Nestes dois processos, o resultado poderá ser reduzido de acordo com o exposto no tópico anterior.


## Como integrar com o modelo? ##

Crie suas classes livremente no diretório modelo.
Você não precisará escrever nenhum include em seus códigos.
Basta que suas classes declarem uma instrução namespace condizente com o diretório onde estão.
Por exemplo:

namespace modelo;            -> Para classes salvas diretamente no modelo
namespace modelo\dados;      -> Para classes salvas no subdiretório dados do modelo

E assim por diante para toda e qualquer classe que você venha a criar.
Assim como o padrão de classloading de linguagens como Java, os arquivos das classes
devem ter o mesmo nome delas, com a extensão .php.

Se quiser alterar esse comportamento, edite o arquivo Autoload.php de config.

Você pode carregar bibliotecas (libs) suas ou de terceiros (que possuam seu próprio 
mecanismo de autoload) chamando a instrução:

Facil::carregarLib('PDO'); 

Onde PDO é o prefixo de um arquivo chamado PDOPlugin.php presente no diretório lib.

Para integrar uma nova lib, crie um novo arquivo NovaLibPlugin.php em lib e nele crie
uma classe que implemente IPlugin.

Esta interface exige apenas que você declare o método carregar(), útil, por exemplo, para que você possa
dar include em algum arquivo que poderá conter instruções spl_autoload_register()
para o autoloading das demais classes da lib que você deseja integrar.


License
=======================

Ainda estamos definindo nossos termos de licença
Copyleft (C) 2012 Jose Berardo - Comunidade PHP Pernambuco
