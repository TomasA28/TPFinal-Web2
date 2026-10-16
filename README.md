# TPFinal-Web2

<h3>Juego de preguntas y respuestas.</h3>
<p>Nos contrata una empresa que quiere hacer juegos de preguntas y respuestas. El juego será web,
pero debe permitir visualizar bien desde celulares. Queda por definir el nombre del juego y queda
a nuestra elección.</p>

<p>La primer interacción que tendremos con el juego nos solicitará registrarnos, pidiendo nombre
completo, año de nacimiento, sexo (Masculino ,Femenino, Prefiero no cargarlo ) , Pais y ciudad
(seleccionado desde un mapa), mail, contraseña (con campo para repetir contraseña), nombre de
usuario, foto de perfil. Una vez ingresado dicho formulario, se nos enviará un mail de validación de
la cuenta con un link que habilitará nuestra cuenta.
</p>

<p>Con dicha cuenta creada, utilizando nombre de usuario y contraseña, se podría ingresar al lobby de
la aplicación.</p>

<p>El lobby consta de un título que indique nuestro nombre y puntaje alcanzado en el ranking, un
botón para crear nuevas partidas, un botón al ranking, donde muestre los puntajes acumulados
totales de todos los jugadores y un listado de las partidas que jugamos con su resultado.</p>

<p>Haciendo click en esos jugadores, tengo que poder ver el perfil de ese jugador con sus datos (mapa
incluido), con su nombre, puntaje final y partidas realizadas, y un QR para navegar rápidamente a
su perfil.</p>

<p>El juego en sí consta de responder preguntas aleatorias del tipo ABCD, donde cada respuesta válida
acumula un punto, en cuanto nos equivocamos, perdemos la partida y nos indica que ganamos X
puntos, o que la respuesta correcta era otra. Cada pregunta tiene una categoría (historia, deportes,
cultura, …). Dicha categoría se define por un color y pinta alguna parte de la pantalla, para
entender de qué categoría es la pregunta.</p>

<p>Cómo usuario, también podemos reportar que una pregunta es inválida (desde la pantalla de juego
cuando se muestra la pregunta), y crear preguntas nuevas (desde el lobby).</p>

<p>Debe existir un tipo de usuario editor, que le permite dar de alta, baja y modificar las preguntas. A
su vez puede revisar las preguntas reportadas, para aprobar o dar de baja, y aprobar las preguntas
sugeridas por usuarios.
</p>

<p>Por otro lado debe existir el usuario administrador, capaz de ver la cantidad de jugadores que tiene
la aplicación, cantidad de partidas jugadas, cantidad de preguntas en el juego, cantidad de
preguntas creadas, cantidad de usuarios nuevos, porcentaje de preguntas respondidas
correctamente por usuario, cantidad de usuarios por pais, cantidad de usuarios por sexo, cantidad
de usuarios por grupo de edad (menores, jubilados, medio). Todos estos gráficos deben poder
filtrarse por día, semana, mes o año. Estos reportes tienen que poder imprimirse (al menos las
tablas de datos).</p>

<h3>Por definir alcance:</h3>
<h4>Para evitar la facilidad:</h4>
<ul>
  <li>
    Los usuarios no deben ver preguntas que ya hayan visto, salvo que ya no haya más preguntas
  </li>
  <li>
    Entregar preguntas de la dificultad/nivel del usuario. Si la pregunta se responde bien
    más del 70% de las veces, es fácil. Si se responde menos del 30% es dificil.
    La pregunta entregada debería ser acorde al ratio de respuestas correctas del usuario
  </li>
</ul>

<h4>Para comenzar a monetizar:</h4>
<ul>
  <li>
    Vamos a vender trampitas, una trampita es un producto que se cobra 1 dolar, y
    permite responder una pregunta correctamente sin saber su respuesta.
  </li>
  <li>
    El usuario tiene que ver en algun lado de su pantalla cuantas trampitas le queda.
  </li>
  <li>
    El usuario tiene que poder comprar esas trampitas haciendo click en ellas (por
    ahora simular el pago, pero es posible que haya que integrarlo a mercadoPago).
  </li>
  <li>
    El administrador quiere ver cual es el balance de trampitas acumulados por el
    usuario.
  </li>
  <li>
    El administrador quiere saber cuánta plata está ganando con las trampitas.
  </li>
</ul>

<h4>Para favorecer el uso social:</h4>
<ul>
  <li>
    Crear modo de juego entre 2 personas. Inicialmente la otra persona será un bot.
  </li>
  <li>
    Un jugador puede desafiar una partida a otro jugador desde su perfil, ganará el que
    más preguntas responda correctamente. En la lista de partidas se mostrará que fue
    entre jugadores.
  </li>
  <li>
    El jugador que fue desafiado, verá la partida en espera en su lobby.
  </li>
</ul>

<h4>Para lograr venderlo a terceros:</h4>
<ul>
  <li>
    Comenzamos a ofrecer el producto a empresas y colegios, para que puedan tomar
    exámenes jugando, o pasar el tiempo en las salas de espera de los hospitales.
  </li>
  <li>
    Para ello, necesitamos que los usuarios que se los marca de algun modo, reciban
    preguntas propias de ese entorno. Ejemplo, si pablo entra al banco galicia y escanea
    el código QR del banco, la siguiente hora, las preguntas que le toquen serán las
    creadas por el banco y el ranking que verá será el de las personas que juegan en el
    banco, y no el ranking global.
  </li>
</ul>

<h4>Aumentar la UX:</h4>
<ul>
  <li>Se desean agregar animaciones y transiciones.</li>
  <li> Se desea agregar música (la cual puede apagarse).</li>
</ul>

<h3>Condiciones de realización de la práctica:</h3>
<ul>
  <li>
    El sitio debe realizarse en PHP, con una base de datos mysql o postgre sql.
  </li>
  <li>
    El trabajo se realiza de forma grupal de a 4 integrantes.
  </li>
  <li>
    El trabajo se entregará semana a semana mostrando avance.
  </li>
  <li>
    Al finalizar la materia se expondrá el trabajo completo y dará defensa oral del
    mismo.
  </li>
  <li>
    La lógica y validaciones debe estar íntegramente del lado del servidor, la lógica
    del cliente puede ser para mejorar la UX, pronta respuesta o animaciones.
  </li>
  <li>
    No es posible utilizar frameworks, sino que el desarrollo debe realizarse desde 
    cero (salvo el modelo de ejemplo MVC dado en clase).
  </li>
  <li>
    Consultar por el agregado de libs de terceros a embeber en el proyecto.
  </li>
  <li>
    El ejercicio resuelto debe ser enviado mediante la plataforma MIeL, mediante la
    opción prácticas, en el formato digital solicitado y comprimido mediante 7zip o
    similar en formato ZIP.
  </li>
  <li>
    Asimismo, se deberá crear un proyecto compartido en una plataforma Git (GitLab o
    GitHub).
  </li>
</ul>


<ul>  
  <li>
    Features para trabajar con la facilidad de uso del juego.
  </li>
  <li>
    Features para monetizar el juego : Trampitas.
  </li>
  <li>
    Features para que el juego sea social.
  </li>
  <li>
    Features para vender a terceros.
  </li>
</ul>
