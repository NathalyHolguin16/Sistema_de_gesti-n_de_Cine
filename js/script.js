const peliculas = [
  {
    id: 1,
    titulo: "Inception",
    genero: "Drama",
    duracion: 148,
    sinopsis: "Un ladrón que roba secretos corporativos a través del uso de la tecnología de sueños compartidos.",
    sala: "Sala 1",
    precio: 5.00,
    poster: "../resources/Shawshank.jpg",
    clasificacion: "+12 años"
  },
  {
    id: 2,
    titulo: "El Gran Showman",
    genero: "Musical",
    duracion: 105,
    sinopsis: "Historia de un visionario que crea un espectáculo sin igual.",
    sala: "Sala 2",
    precio: 4.50,
    poster: "../resources/El Gran Showman.jpg",
    clasificacion: "Todo público"
  }
];

const funciones = [
  { id: 1, peliculaId: 1, fechaHora: "2025-06-01 18:00" },
  { id: 2, peliculaId: 1, fechaHora: "2025-06-01 21:00" },
  { id: 3, peliculaId: 2, fechaHora: "2025-06-02 19:00" },
  { id: 4, peliculaId: 2, fechaHora: "2025-06-02 22:00" }
];

// Mostrar cartelera con géneros de colores y botón rojo
const peliculasGrid = document.getElementById('peliculasGrid');
function cargarPeliculas() {
  peliculasGrid.innerHTML = "";
  peliculas.forEach(p => {
    const card = document.createElement('div');
    card.className = 'pelicula-card';
    const generoClase = "genero " + p.genero.toLowerCase().replace(/ /g, "-");
    card.innerHTML = `
      <img src="${p.poster}" alt="${p.titulo}" class="pelicula-poster" />
      <div class="pelicula-info">
        <h3>${p.titulo}</h3>
        <p class="${generoClase}">${p.genero}</p>
        <p class="clasificacion">${p.clasificacion}</p>
        <button class="ver-funciones-btn" onclick="verFunciones(${p.id})">Ver funciones</button>
      </div>
    `;
    peliculasGrid.appendChild(card);
  });
}

// Mostrar funciones y detalles
window.verFunciones = function(peliculaId) {
  const pelicula = peliculas.find(p => p.id === peliculaId);
  document.getElementById('peliculaTitulo').textContent = pelicula.titulo;
  document.getElementById('sinopsis').textContent = pelicula.sinopsis;
  document.getElementById('duracion').textContent = pelicula.duracion + " min";
  document.getElementById('sala').textContent = pelicula.sala;
  document.getElementById('precio').textContent = pelicula.precio.toFixed(2);

  const funcs = funciones.filter(f => f.peliculaId === peliculaId);
  const funcionListaDiv = document.getElementById('funcionLista');
  funcionListaDiv.innerHTML = "";
  funcs.forEach(f => {
    const btn = document.createElement('button');
    btn.textContent = new Date(f.fechaHora).toLocaleString();
    btn.onclick = () => {
      showSection('reservas');
      seleccionarPeliculaFuncion(peliculaId, f.id);
    };
    funcionListaDiv.appendChild(btn);
  });

  showSection('funciones');
};

// Mostrar/ocultar secciones
function showSection(seccion) {
  document.getElementById('cartelera').style.display = 'none';
  document.getElementById('funciones').style.display = 'none';
  document.getElementById('reservas').style.display = 'none';
  document.getElementById('admin').style.display = 'none';
  document.getElementById(seccion).style.display = 'block';
}

// --- RESERVAS ---
const peliculaSelect = document.getElementById('peliculaSelect');
const funcionSelect = document.getElementById('funcionSelect');

function llenarPeliculasSelect() {
  peliculaSelect.innerHTML = "";
  peliculas.forEach(p => {
    const option = document.createElement('option');
    option.value = p.id;
    option.textContent = p.titulo;
    peliculaSelect.appendChild(option);
  });
}

function llenarFuncionesSelect(peliculaId) {
  funcionSelect.innerHTML = "";
  const funcs = funciones.filter(f => f.peliculaId == peliculaId);
  funcs.forEach(f => {
    const option = document.createElement('option');
    option.value = f.id;
    option.textContent = new Date(f.fechaHora).toLocaleString();
    funcionSelect.appendChild(option);
  });
}

function seleccionarPeliculaFuncion(peliculaId, funcionId) {
  llenarPeliculasSelect();
  peliculaSelect.value = peliculaId;
  llenarFuncionesSelect(peliculaId);
  funcionSelect.value = funcionId;
  actualizarResumenCompra();
}

peliculaSelect.addEventListener('change', () => {
  llenarFuncionesSelect(peliculaSelect.value);
  actualizarResumenCompra();
});
funcionSelect.addEventListener('change', actualizarResumenCompra);
document.getElementById('numEntradas').addEventListener('input', actualizarResumenCompra);

function obtenerPrecioFuncionSeleccionada() {
  const peliculaId = parseInt(peliculaSelect.value);
  const pelicula = peliculas.find(p => p.id === peliculaId);
  return pelicula ? pelicula.precio : 0;
}

function actualizarResumenCompra() {
  const numEntradas = parseInt(document.getElementById('numEntradas').value) || 1;
  const precio = obtenerPrecioFuncionSeleccionada();
  const montoTotal = numEntradas * precio;
  document.getElementById('montoTotal').textContent = montoTotal.toFixed(2);

  const ahora = new Date();
  document.getElementById('fechaHoraCompra').textContent = ahora.toLocaleString();
}

// Enviar reserva
const reservaForm = document.getElementById('reservaForm');
reservaForm.addEventListener('submit', (e) => {
  e.preventDefault();
  alert(`Reserva confirmada para ${document.getElementById('nombreCliente').value} en la película "${peliculas.find(p => p.id == peliculaSelect.value).titulo}" en la función seleccionada.`);
  reservaForm.reset();
  showSection('cartelera');
});

// --- ADMIN ---
document.getElementById('empleadoForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const nombre = document.getElementById('nombreEmpleado').value;
  const cargo = document.getElementById('cargoEmpleado').value;
  const usuario = document.getElementById('usuarioEmpleado').value;
  const contrasena = document.getElementById('contrasenaEmpleado').value;
  const contrasenaCifrada = btoa(contrasena);

  const empleado = { nombre, cargo, usuario, contrasena: contrasenaCifrada };
  const lista = document.getElementById('listaEmpleados');
  const item = document.createElement('div');
  item.textContent = `Nombre: ${nombre}, Cargo: ${cargo}, Usuario: ${usuario}, Contraseña cifrada: ${contrasenaCifrada}`;
  lista.appendChild(item);

  this.reset();
});

// Inicialización
cargarPeliculas();
llenarPeliculasSelect();
llenarFuncionesSelect(peliculaSelect.value);
showSection('cartelera');
