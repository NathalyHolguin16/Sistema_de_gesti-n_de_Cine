const seats = document.querySelectorAll('.seat:not(.occupied)');
const selectedList = document.getElementById('selected-list');

seats.forEach(seat => {
  seat.addEventListener('click', () => {
    if (!seat.classList.contains('occupied')) {
      seat.classList.toggle('selected');
      updateSelectedSeats();
    }
  });
});

function updateSelectedSeats() {
  const selectedSeats = document.querySelectorAll('.seat.selected');
  const seatNumbers = [...selectedSeats].map(seat => seat.getAttribute('data-seat'));
  selectedList.textContent = seatNumbers.length > 0 ? seatNumbers.join(', ') : 'ninguno';
}
