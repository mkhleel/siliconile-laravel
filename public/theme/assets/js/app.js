// Mobile menu functionality using Alpine.js
document.addEventListener('alpine:init', () => {
  Alpine.data('mobileMenu', () => ({
    isOpen: false,
    toggle() {
      this.isOpen = !this.isOpen
    },
    close() {
      this.isOpen = false
    }
  }))
})
