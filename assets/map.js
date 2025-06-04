(function($){
  $(function(){
    if($('#zxtec-map').length){
      var map = L.map('zxtec-map').setView([-23.55, -46.63], 12);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'Map data © OpenStreetMap contributors'
      }).addTo(map);
      if(window.ZXTEC_CLIENTS){
        ZXTEC_CLIENTS.forEach(function(c){
          L.marker([c.lat, c.lng]).addTo(map).bindPopup(c.title);
        });
      }
      if(window.ZXTEC_TECHS){
        ZXTEC_TECHS.forEach(function(t){
          L.marker([t.lat, t.lng], {icon: L.icon({iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png'})}).addTo(map).bindPopup(t.name);
        });
      }
    }
  });
})(jQuery);
