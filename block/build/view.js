document.querySelectorAll(".hm-toc, .hm-table-of-contents").forEach((e=>{const t=e.querySelectorAll("a"),n=[...[...t].map((e=>new URL(e.href).hash.substring(1))).map((e=>document.getElementById(e)))].reverse(),r=n.map((e=>e.getBoundingClientRect().top));let o=1/0;window.addEventListener("scroll",(()=>{requestAnimationFrame((()=>{const l=document.documentElement.scrollTop,a=window.innerHeight;let c=1/0;for(let e of r)if(e<=l+a/2){c=e;break}if(c===o)return;o=c;const i=n[r.indexOf(o)].getAttribute("id"),s=e.querySelector(`a[href="#${i}"]`);s&&(t.forEach((e=>e.parentElement.classList.remove("active"))),s.parentElement.classList.add("active"))}))}),{passive:!0})}));