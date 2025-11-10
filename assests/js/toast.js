// Simple toast function
function showToast(message, type='success') {
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerText = message;
  document.body.appendChild(toast);
  setTimeout(()=>toast.classList.add('show'),100);
  setTimeout(()=>toast.remove(),4000);
}
