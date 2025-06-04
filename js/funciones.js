function getIdPeliculaFromUrl() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id_pelicula');
}

document.addEventListener('DOMContentLoaded', async () => {
  const id_pelicula = getIdPeliculaFromUrl();
  const posterImg = document.getElementById('peliculaPoster');

  if (!id_pelicula) {
    document.getElementById('funciones').innerHTML = "<div class='mensaje-error'>No se ha seleccionado ninguna película.</div>";
    return;
  }

  // Cargar datos de la película
  const resPeli = await fetch(`../php/peliculas.php?id_pelicula=${id_pelicula}`);
  const pelicula = await resPeli.json();

  document.getElementById('peliculaTitulo').textContent = pelicula.titulo;
  document.getElementById('sinopsis').textContent = pelicula.sinopsis;
  document.getElementById('duracion').textContent = pelicula.duracion_minutos + " min";
  document.getElementById('clasificacion').textContent = pelicula.clasificacion || '';
  document.getElementById('genero').textContent = pelicula.genero || '';
  posterImg.src = pelicula.imagen ? `../resources/${pelicula.imagen}` : "../resources/peli_683293c9484d4.jpg";
  posterImg.alt = pelicula.titulo;

  console.log('Película:', pelicula);

  // Cargar funciones de la película
  let funciones = [];
  try {
    const resFunciones = await fetch(`../php/funciones.php?id_pelicula=${id_pelicula}`);
    funciones = await resFunciones.json();
    console.log('Funciones:', funciones); // <-- Aquí
  } catch (e) {
    document.getElementById('funcionLista').innerHTML = "<div class='mensaje-error'>Error al cargar las funciones.</div>";
    console.error(e);
    return;
  }

  // Agrupar funciones por fecha
  const funcionesPorFecha = {};
  funciones.forEach(f => {
    if (!funcionesPorFecha[f.fecha]) funcionesPorFecha[f.fecha] = [];
    funcionesPorFecha[f.fecha].push(f);
  });

  const funcionListaDiv = document.getElementById('funcionLista');
  funcionListaDiv.innerHTML = "";

  function getNombreDia(fechaStr) {
    const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    const fechaObj = new Date(fechaStr);
    return dias[fechaObj.getDay()];
  }

  if (!funciones || funciones.length === 0) {
    funcionListaDiv.innerHTML = "<div class='mensaje-info'>No hay funciones programadas para esta película.</div>";
  } else {
    Object.keys(funcionesPorFecha).forEach(fecha => {
      const fechaDiv = document.createElement('div');
      fechaDiv.className = 'funcion-fecha-bloque';
      const nombreDia = getNombreDia(fecha);
      fechaDiv.innerHTML = `<div class="funcion-fecha-titulo"><b>${nombreDia} ${fecha}</b></div>`;
      funcionesPorFecha[fecha].forEach(f => {
        const btn = document.createElement('button');
        btn.className = 'funcion-hora-btn';
        btn.textContent = f.hora_inicio;
        btn.onclick = () => window.location.href = `reservas.html?id_funcion=${f.id_funcion}`;
        fechaDiv.appendChild(btn);
      });
      funcionListaDiv.appendChild(fechaDiv);
    });
  }
});