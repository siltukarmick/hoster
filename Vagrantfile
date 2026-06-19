Vagrant.configure(2) do |config|
  config.vm.box = "ubuntu/jammy64"
  config.vm.hostname = "hoster"
  config.vm.box_check_update = false
  config.vm.network "private_network", ip: "192.168.62.101"
  config.vm.synced_folder "..", "/mnt/components/"
  config.vm.provider "virtualbox" do |vb|
  vb.memory = "4096"
end
config.vm.provision "shell", inline: <<-SHELL
  export DEBIAN_FRONTEND=noninteractive

  rm /etc/resolv.conf
  echo "nameserver 8.8.8.8" > /etc/resolv.conf

  sudo apt-get update

  sudo apt-get -y install \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg-agent \
    software-properties-common

  curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -

  sudo apt-key fingerprint 0EBFCD88

  sudo add-apt-repository \
     "deb [arch=amd64] https://download.docker.com/linux/ubuntu \
     $(lsb_release -cs) \
     stable"

  sudo apt-get update

  sudo apt-get -y install nodejs jq docker-ce docker-compose gnupg2 pass sshpass

SHELL
end