var toggleTocElement = document.getElementById("toc-toggle");

if (toggleTocElement) {
    toggleTocElement.addEventListener("click", function() {
        var tocElement = document.getElementById("toc");
        if (tocElement) {
            var xElement = tocElement.querySelector(".toc-hide, .toc-show");
            if (xElement) {
                if (xElement.classList.contains("toc-hide")) {
                    xElement.classList.remove("toc-hide");
                    xElement.classList.add("toc-show");
                    this.innerText = this.getAttribute('data-hide-text');
                } else if (xElement.classList.contains("toc-show")) {
                    xElement.classList.remove("toc-show");
                    xElement.classList.add("toc-hide");
                    this.innerText = this.getAttribute('data-show-text');
                }
            }
        }
    });
}
